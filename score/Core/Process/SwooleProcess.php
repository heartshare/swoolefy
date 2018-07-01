<?php
/**
+----------------------------------------------------------------------
| swoolefy framework bases on swoole extension development, we can use it easily!
+----------------------------------------------------------------------
| Licensed ( https://opensource.org/licenses/MIT )
+----------------------------------------------------------------------
| Author: bingcool <bingcoolhuang@gmail.com || 2437667702@qq.com>
+----------------------------------------------------------------------
*/
 
namespace Swoolefy\Core\Process;

use Swoolefy\Core\Swfy;
use Swoolefy\Core\Hook;
use Swoolefy\Core\ZModel;
use Swoolefy\Core\BaseObject;
use Swoolefy\Core\Application;

class SwooleProcess extends BaseObject {

	/**
	 * $config 当前应用层的配置 
	 * @var null
	 */
	public $config = null;

	/**
	 * $event_hooks 钩子事件
	 * @var array
	 */
	public $event_hooks = [];
	const HOOK_AFTER_REQUEST = 1;

	/**
 	 * $ExceptionHanderClass 异常处理类
 	 * @var string
 	 */
 	private $ExceptionHanderClass = 'Swoolefy\\Core\\SwoolefyException';

 	/**
	 * __construct
	 * @param $config 应用层配置
	 */
	public function __construct(array $config = []) {
		// 将应用层配置保存在上下文的服务
		$this->config = array_merge(Swfy::$appConfig, $config);
		// 注册错误处理事件
		$protocol_config = Swfy::getConf();
		if(isset($protocol_config['exception_hander_class']) && !empty($protocol_config['exception_hander_class'])) {
			$this->ExceptionHanderClass = $protocol_config['exception_hander_class'];
		}
		register_shutdown_function($this->ExceptionHanderClass.'::fatalError');
      	set_error_handler($this->ExceptionHanderClass.'::appError');
	}

 	/**
	 * afterRequest 
	 * @param  callable $callback
	 * @param  boolean  $prepend
	 * @return mixed
	 */
	public function afterRequest(callable $callback, $prepend = false) {
		if(is_callable($callback, true, $callable_name)) {
			$key = md5($callable_name);
			if($prepend) {
				if(!isset($this->event_hooks[self::HOOK_AFTER_REQUEST])) {
					$this->event_hooks[self::HOOK_AFTER_REQUEST] = [];
				}
				if(!isset($this->event_hooks[self::HOOK_AFTER_REQUEST][$key])) {
					$this->event_hooks[self::HOOK_AFTER_REQUEST][$key] = array_merge([$key=>$callback], $this->event_hooks[self::HOOK_AFTER_REQUEST]);
				}
				return true;
			}else {
				// 防止重复设置
				if(!isset($this->event_hooks[self::HOOK_AFTER_REQUEST][$key])) {
					$this->event_hooks[self::HOOK_AFTER_REQUEST][$key] = $callback;
				}
				return true;
			}
		}else {
			throw new \Exception(__NAMESPACE__.'::'.__function__.' the first param of type is callable');
		}
		
	}

	/**
	 * callEventHook 
	 * @return void
	 */
	public function callAfterEventHook() {
		if(isset($this->event_hooks[self::HOOK_AFTER_REQUEST]) && !empty($this->event_hooks[self::HOOK_AFTER_REQUEST])) {
			foreach($this->event_hooks[self::HOOK_AFTER_REQUEST] as $func) {
				$func();
			}
		}
	}

	/**
	 * end
	 * @return  
	 */
	public function end() {
		if(method_exists($this,'_afterAction')) {
			static::_afterAction();
		}
		// callhooks
		$this->callAfterEventHook();
		// destroy
		Application::removeApp();
	}

 	use \Swoolefy\Core\ComponentTrait;
}