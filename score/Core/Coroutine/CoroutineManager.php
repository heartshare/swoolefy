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
			$main_cid = co::getuid();
			return $main_cid;
		}

		if(isset(self::$cid) && !empty(self::$cid)) {
			return self::$cid;
		}
		self::$cid = time();
		return self::$cid;
		
	}

	public function setTest() {

	}
}