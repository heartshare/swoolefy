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

namespace Swoolefy\Core;

use Swoolefy\Core\Application;

class EventApp {
	/**
	 * $event_app 事件处理应用对象
	 * @var [type]
	 */
	public $event_app;

	/**
	 * registerApp 注册事件处理应用对象，注册一次处理事件
	 * 可用于onConnect, onOpen, onPipeMessage,onHandShake, onClose
	 * 例如在close事件，App\Event\Close需要继承于\Swoolefy\Core\EventController
	 *
	 * public function onClose($server, $fd) {
		   // 只需要注册一次就好
		   (new \Swoolefy\Core\EventApp())->registerApp(\App\Event\Close::class, $server, $fd)->close();
	 *  }
	 *
	 * 那么处理类
	 * class Close extends EventController {
	     	// 继承于EventController，可以传入可变参数
			public function __construct($server, $fd) {
				// 必须执行父类__construct()
				parent::__construct();
			}

			public function close() {
				//TODO
			}
	*  }
	 * @param  string $class
	 * @return $this
	 */
	public function registerApp($class, ...$args) {
		if(is_object($class)) {
			$this->event_app = $class;
		}
		if(is_string($class)) {
			$this->event_app = new $class(...$args);
		}

		if(!($this->event_app instanceof \Swoolefy\Core\EventController)) {
			unset($this->event_app);
			throw new \Exception("$class must extends \Swoolefy\Core\EventController");
		}

		return $this;
	}

	/**
	 * getAppCid 获取当前应用实例的协程id
	 * @return  
	 */
	public function getCid() {
		return $this->event_app->getCid();
	}

	/**
	 * __call 
	 * @param  string $action
	 * @param  array  $args
	 * return  $this
	 */
	public function __call(string $action, $args = []) {
		return call_user_func_array([$this->event_app, $action], $args);
	}

	/**
	 * __destruct 
	 */
	public function __destruct() {
		Application::removeApp();
		unset($this->event_app);
	}
}