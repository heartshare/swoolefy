<?php 
namespace Swoolefy\Core\Coroutine;

use Swoolefy\Core\Swfy;
use Swoolefy\Core\BaseServer;

class CoroutineManager {

	use \Swoolefy\Core\SingletonTrait;

	protected static $cid = null;

	/**
	 * isEnableCoroutine 
	 * @return   boolean
	 */
	public  function isEnableCoroutine() {
		return BaseServer::isEnableCoroutine();
	}
	
	/**
	 * getMainCoroutineId 获取协程的id
	 * @return 
	 */
	public function getCoroutineId() {
		if($this->isEnableCoroutine()) {
			$cid = \co::getuid();
			// 在task|process中不直接支持使用协程
			if($cid == -1) {
				$cid = 'cid_task_process';
			}
			$cid = 'cid_'.$cid;
			return $cid;
		}

		if(isset(self::$cid) && !empty(self::$cid)) {
			return self::$cid;
		}
		self::$cid = (string)time().rand(1,999);
		return self::$cid;
		
	}

	/**
	 * getCoroutinStatus 
	 * @return   array
	 */
	public function getCoroutinStatus() {
		if(method_exists('co', 'stats')) {
			return \co::stats();
		}
		return null;
		
	}

}