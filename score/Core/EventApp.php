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
	 * registerApp 注册事件处理应用对象
	 * @param  string $class
	 * @return $this
	 */
	public function registerApp($class) {
		if(is_object($class)) {
			$this->event_app = $class;
		}
		if(is_string($class)) {
			$this->event_app = new $class;
		}

		if(!($this->event_app instanceof \Swoolefy\Core\EventController)) {
			unset($this->event_app);
			throw new \Exception("$class must extends \Swoolefy\Core\EventController");
		}
		
		return $this;
	}

	/**
	 * __call 
	 * @param  string $action
	 * @param  array  $args
	 * return  $this
	 */
	public function __call(string $action, $args = []) {
		call_user_func_array([$this->event_app, $action], $args);
		return $this;
	}

	/**
	 * __destruct 
	 */
	public function __destruct() {
		Application::removeApp();
	}
}