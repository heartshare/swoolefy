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

use Swoolefy\Core\Coroutine\CoroutineManager;

class Application {
	/**
	 * $app 应用对象
	 * @var null
	 */
	public static $app = null;

	/**
	 * $dump 记录启动时的调试打印信息
	 * @var null
	 */
	public static $dump = null;

	/**
	 * setApp 
	 * @param $object
	 */
	public static function setApp($obj) {
		$cid = $obj->coroutine_id;
		self::$app[$cid] = $obj;
		return true;
	}

	/**
	 * getApp 
	 * @param  int|null $coroutine_id
	 * @return $object
	 */
	public static function getApp(int $coroutine_id = null) {
		$cid = CoroutineManager::getInstance()->getCoroutineId();
		if(isset(self::$app[$cid])) {
			return self::$app[$cid];
		}
		return null;
	}

	/**
	 * removeApp 
	 * @param  int|null $coroutine_id
	 * @return boolean
	 */
	public static function removeApp(int $coroutine_id = null) {
		$cid = CoroutineManager::getInstance()->getCoroutineId();
		if(isset(self::$app[$cid])) {
			unset(self::$app[$cid]);
		}
		return true;
	} 

	/**
	 * __construct
	 */
	public function __construct() {
		
	}

	/**
	 * __destruct
	 */
	public function __destruct() {
	}
}