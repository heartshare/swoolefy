<?php 
namespace Swoolefy\Core\Coroutine;

use co;
use Swoolefy\Core\Swfy;
use Swoolefy\Core\BaseServer;

class CoroutineManager {

	use \Swoolefy\Core\SingletonTrait;

	protected static $cid = null;

	protected static $pools = [];

	protected static $coroutine_ids = [];

	public  function isEnableCoroutine() {
		return BaseServer::isEnableCoroutine();
	}
	
	/**
	 * getMainCoroutineId 获取协程的id
	 * @return 
	 */
	public function getCoroutineId() {
		if($this->isEnableCoroutine()) {
			$cid = co::getuid();
			// 在task|process中不直接支持使用协程
			if($cid == -1) {
				$cid = 'task_process';
			}
			return $cid;
		}

		if(isset(self::$cid) && !empty(self::$cid)) {
			return self::$cid;
		}
		self::$cid = (string)time().rand(1,999);
		return self::$cid;
		
	}

	public function setTest() {

	}
}