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

namespace Swoolefy\Core\Task;

use Swoolefy\Core\Swfy;
use Swoolefy\Core\BaseObject;
use Swoolefy\Core\Hook;
use Swoolefy\Core\Application;

class TaskController extends BaseObject {
	/**
	 * $from_worker_id 记录当前任务from的woker投递
	 * @see https://wiki.swoole.com/wiki/page/134.html
	 * @var null
	 */
	public $from_worker_id = null;

	/**
	 * $task_id 任务的ID
	 * @see  https://wiki.swoole.com/wiki/page/134.html
	 * @var null
	 */
	public $task_id = null;
	
	/**
	 * $config 应用层配置
	 * @var null
	 */
	public $config = null;

	/**
	 * $selfModel 控制器对应的自身model
	 * @var array
	 */
	public $selfModel = [];

	/**
	 * $event_hooks 钩子事件
	 * @var array
	 */
	public $event_hooks = [];
	const HOOK_AFTER_REQUEST = 1;

	/**
	 * __construct 初始化函数
	 */
	public function __construct() {
		Application::setApp($this);
		// 应用层配置
		$this->config = Swfy::$appConfig;
	}

	/**
	 * beforeAction 在处理实际action之前执行
	 * @return   mixed
	 */
	public function _beforeAction() {
		return true;
	}

	/**
	 * afterAction 在返回数据之前执行
	 * @return   mixed
	 */
	public function _afterAction() {
		return true;
	}

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
	 * __destruct 返回数据之前执行,重新初始化一些静态变量
	 */
	public function __destruct() {

		if(method_exists($this,'_afterAction')) {
			static::_afterAction();
		}
		// destroy
		Application::removeApp();

	}

	use \Swoolefy\Core\ComponentTrait,\Swoolefy\Core\ServiceTrait;
	
}