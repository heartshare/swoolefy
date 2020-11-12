<?php
/**
+----------------------------------------------------------------------
| swoolefy framework bases on swoole extension development, we can use it easily!
+----------------------------------------------------------------------
| Licensed ( https://opensource.org/licenses/MIT )
+----------------------------------------------------------------------
| @see https://github.com/bingcool/swoolefy
+----------------------------------------------------------------------
*/

namespace Swoolefy\Core;

use Swoolefy\Core\Swfy;
use Swoolefy\Core\ZFactory;
use Swoolefy\Core\AppInit;
use Swoole\Http\Request as swooleRequest;
use Swoole\Http\Response as swooleResponse;
use Swoolefy\Core\HttpRoute;
use Swoolefy\Core\BaseServer;
use Swoolefy\Core\Application;
use Swoolefy\Core\Log\LogManager;
use Swoolefy\Core\Controller\BController;
use Swoolefy\Core\Coroutine\CoroutineManager;
use Swoolefy\Http\Psr\Request;
use Swoolefy\Http\Psr\Response;
use Swoolefy\Http\Psr\Environment;
use Swoolefy\Http\Psr\Body;


class App extends \Swoolefy\Core\Component {

    use \Swoolefy\Core\AppTrait,\Swoolefy\Core\ServiceTrait;

    /**
	 * $swooleRequest swoole当前请求的对象
	 * @var swooleRequest
	 */
	public $swooleRequest = null;

	/**
	 * $swooleResponse swoole当前请求的响应对象
	 * @var swooleResponse
	 */
	public $swooleResponse = null;

	/**
	 * $app_conf 当前应用层的配置
	 * @var array
	 */
	public $app_conf = null;

	/**
	 * $coroutine_id 
	 * @var int
	 */
	public $coroutine_id;

    /**
     * $controllerInstance 控制器实例
     * @var BController
     */
    protected $controllerInstance = null;

    /**
     * @var bool
     */
    protected $is_end = false;

    /**
     * $is_defer
     * @var bool
     */
    protected $is_defer = false;

	/**
	 * __construct
	 * @param array $config 应用层配置
	 */
	public function __construct(array $conf = []) {
		$this->app_conf = $conf;
	}

    /**
     * init 初始化函数
     * @return void
     * @throws \Exception
     */
	protected function _init() {
		if(isset($this->app_conf['session_start']) && $this->app_conf['session_start']) {
			if(is_object($this->get('session'))) {
				$this->get('session')->start();
			};
		}
	}

    /**
     * @param $request
     */
	protected function _bootstrap() {
        $conf = BaseServer::getConf();
	    if(isset($conf['application_index'])) {
	    	$application_index = $conf['application_index'];
	    	if(class_exists($application_index)) {
            	$conf['application_index']::bootstrap($this->getRequestParams());
        	}
        }
	}

	/**
	 * run 执行
	 * @param  $swooleRequest
	 * @param  $swooleResponse
     * @throws \Throwable
	 * @return mixed
	 */
	public function run($swooleRequest, $swooleResponse, $extend_data = null) {
	    try {
            AppInit::init($swooleRequest);
            $this->swooleRequest = $swooleRequest;
            $this->swooleResponse = $swooleResponse;
            $this->coroutine_id = CoroutineManager::getInstance()->getCoroutineId();
            $this->registerDefaultComponents();
            parent::creatObject();
            Application::setApp($this);

            /**@var Response $response */
            $response = $this->container['response'];
            $body = new Body(fopen('php://temp', 'r+'));
            $body->write(json_encode(['name'=>'vvvvvvv']));
            $response = $response->withBody($body);
            $response = $response->withHeader('Content-Type','application/json; charset=UTF-8');
            $this->swooleResponse->write($response->getBody());
            return;


            $this->defer();
            $this->_init();
            $this->_bootstrap();
            if(!$this->catchAll()) {
                $route = new HttpRoute($extend_data);
                $route->dispatch();
            }
        }catch (\Throwable $throwable) {
            throw $throwable;
        }finally {
        	if(!$this->is_defer) {
        		$this->clearStaticVar();
	            $this->end();
        	}
        }
	}

	public function registerDefaultComponents() {
        $server = $this->swooleRequest->server;
        $environment = Environment::mock($server);
        $headers = $this->swooleRequest->header ?? [];
        $cookies = $this->swooleRequest->cookie ?? [];
        $protocols = explode('/', $server['SERVER_PROTOCOL']);
        $httpVersion = array_pop($protocols);

        // register request component
	    parent::creatObject('request', function() use ($environment, $headers, $cookies) {
            $request = \Swoolefy\Http\Psr\Request::createFromEnvironment($environment, $headers, $cookies);
            $request->setSwooleRequest($this->swooleRequest);
            return $request;
        });

	    // register response component
	    parent::creatObject('response', function() use($httpVersion) {
            $headers = new \Swoolefy\Http\Psr\Headers(['Content-Type' => 'application/json; charset=UTF-8']);
            $response = new \Swoolefy\Http\Psr\Response(200, $headers);
            if($httpVersion >= 1.0) {
                return $response->withProtocolVersion($httpVersion);
            }
            return $response;
        });
    }

	/**
	 * setAppConf
	 */
	public function setAppConf(array $conf = []) {
		static $is_reset_app_conf;
		if(!isset($is_reset_app_conf)) {
			if(!empty($conf)) {
				$this->app_conf = $conf;
				Swfy::setAppConf($conf);
				BaseServer::setAppConf($conf);
				$is_reset_app_conf = true;
			}
		}
	}

    /**
     * @param BController $controller
     */
	public function setControllerInstance(BController $controller) {
	    $this->controllerInstance = $controller;
    }

    /**
     * @return |null
     */
    public function getControllerInstance() {
        return $this->controllerInstance;
    }

    /**
	 * catchAll 捕捉拦截所有请求，进入维护模式
	 * @return boolean
	 */
	public function catchAll() {
	    // catchAll
		if(isset($this->app_conf['catch_handle']) && $handle = $this->app_conf['catch_handle']) {
            $this->is_end = true;
			if(is_array($handle)) {
			    /**@var Response $response */
			    $response = $this->container['response'];
                $response->withHeader('Content-Type','application/json; charset=UTF-8');
				$response->write(json_encode($handle, JSON_UNESCAPED_UNICODE));
			}else if($handle instanceof \Closure) {
                $handle->call($this, $this->request, $this->response);
			}else {
                $this->response->header('Content-Type','text/html; charset=UTF-8');
                $this->response->end($handle);
            }
			return true;
		}
		return false;
	}

	/**
	 * afterRequest 请求结束后注册钩子执行操作
	 * @param callable $callback
	 * @param boolean $prepend
	 * @return bool
	 */
	public function afterRequest(callable $callback, bool $prepend = false) {
        return Hook::addHook(Hook::HOOK_AFTER_REQUEST, $callback, $prepend);
    }

    /**
     * getCid
     * @return int
     */
    public function getCid() {
        return $this->coroutine_id;
    }

    /**
     * @return SwoolefyException | string
     */
	public function getExceptionClass() {
        return BaseServer::getExceptionClass();
    }

	/**
	 *clearStaticVar 销毁静态变量
	 * @return void
	 */
	public function clearStaticVar() {
		// call hook callable
		Hook::callHook(Hook::HOOK_AFTER_REQUEST);
	}

    /**
     *pushComponentPools
     * @return bool
     */
    public function pushComponentPools() {
        if(empty($this->component_pools) || empty($this->component_pools_obj_ids)) {
            return false;
        }
        foreach($this->component_pools as $name) {
            if(isset($this->container[$name])) {
                $obj = $this->container[$name];
                if(is_object($obj)) {
                    $obj_id = spl_object_id($obj);
                    if(in_array($obj_id, $this->component_pools_obj_ids)) {
                        \Swoolefy\Core\Coroutine\CoroutinePools::getInstance()->getPool($name)->pushObj($obj);
                    }
                }
            }
        }
    }

    /**
     * setEnd
     * @return void
     */
    public function setEnd() {
        $this->is_end = true;
    }

	/**
	 * end 请求结束
	 * @return void
	 */
	public function end() {
        // remove
        ZFactory::removeInstance();
		// log handle
        $this->handleLog();
        // push obj pools
        $this->pushComponentPools();
        // remove App Instance
		Application::removeApp();
        if(!$this->is_end) {
            @$this->swooleResponse->end();
        }
	}

	/**
	 * defer 
	 * @return void
	 */
	protected function defer() {
		if(\Swoole\Coroutine::getCid() > 0) {
			$this->is_defer = true;
			defer(function() {
			    $this->clearStaticVar();
	            $this->end();
        	});
		}
	}

}