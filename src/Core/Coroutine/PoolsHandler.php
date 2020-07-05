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

namespace Swoolefy\Core\Coroutine;

use Swoole\Coroutine\Channel;

class PoolsHandler {
    /**
     * @var Channel
     */
    protected $channel = null;

    /**
     * @var string
     */
	protected $poolName;

    /**
     * @var int
     */
	protected $poolsNum = 30;

    /**
     * @var int
     */
	protected $pushTimeout = 2;

    /**
     * @var int
     */
	protected $popTimeout = 1;

    /**
     * @var int
     */
	protected $callCount = 0;

    /**
     * @var int
     */
	protected $liveTime = 10;

    /**
     * @param int $poolsNum
     */
	public function setPoolsNum(int $poolsNum = 50) {
		$this->poolsNum = $poolsNum;
	}

    /**
     * @return int
     */
	public function getPoolsNum() {
		return $this->poolsNum;
	}

    /**
     * @param float $pushTimeout
     */
	public function setPushTimeout(float $pushTimeout = 3) {
	    $this->pushTimeout = $pushTimeout;
    }

    /**
     * @return int
     */
    public function getPushTimeout() {
	    return $this->pushTimeout;
    }

    /**
     * @param float $popTimeout
     */
    public function setPopTimeout(float $popTimeout = 1) {
	    $this->popTimeout = $popTimeout;
    }

    /**
     * @return int
     */
    public function getPopTimeout() {
	    return $this->popTimeout;
    }

    /**
     * @param int $liveTime
     */
    public function setLiveTime(int $liveTime) {
	    $this->liveTime = $liveTime;
    }

    /**
     * @return int
     */
    public function getLiveTime() {
	    return $this->liveTime;
    }

    /**
     * @return string
     */
	public function getPoolName() {
		return $this->poolName;
	}

    /**
     * @return int
     */
	public function getCapacity() {
		return $this->channel->capacity;
	}

    /**
     * @return Channel
     */
	public function getChannel() {
		if(isset($this->channel)) {
			return $this->channel;
		}
	}

    /**
     * @param string|null $poolName
     */
	public function registerPools(string $poolName = null) {
		if($poolName) {
			$this->poolName = trim($poolName);
			if(!isset($this->channel)) {
                $this->channel = new Channel($this->poolsNum);
        	}
		}
	}

	/**
	 * pushObj 使用完要重新push进channel
	 * @param  object $obj
	 * @return void
	 */
	public function pushObj($obj) {
		if(is_object($obj)) {
		    go(function() use($obj) {
                $isPush = true;
		        if(isset($obj->objExpireTime) && time() > $obj->objExpireTime) {
		            $isPush = false;
                }

                $length = $this->channel->length();
                if($length >= $this->poolsNum) {
                    $isPush = false;
                }

                if($isPush) {
                    $this->channel->push($obj, $this->pushTimeout);
                    $length = $this->channel->length();
                    // 矫正
                    if(($this->poolsNum - $length) == $this->callCount - 1) {
                        --$this->callCount;
                    }else {
                        $this->callCount = $this->poolsNum - $length;
                    }
                }else {
                    --$this->callCount;
                }

                if($this->callCount < 0) {
                	$this->callCount = 0;
                }
            });
		}
	}

    /**
     * @return mixed
     * @throws \Exception
     */
	public function fetchObj() {
		try {
			$obj = $this->getObj();
            is_object($obj) && $this->callCount++;
            return $obj;
		}catch(\Exception $exception) {
			throw $exception;
		}
	}

	/**
	 * getObj 开发者自行实现
	 * @return mixed
	 */
    protected function getObj() {
        // 第一次开始创建对象
        if($this->callCount == 0 && $this->channel->isEmpty()) {
            if($this->poolsNum) {
                $this->build($this->poolsNum);
            }
            if($this->channel->length() > 0) {
                return $this->pop();
            }
        }else {
            if($this->callCount >= $this->poolsNum) {
                usleep(15 * 1000);
                $length = $this->channel->length();
                if($length > 0) {
                    return $this->pop();
                }else {
                    return null;
                }
            }else {
                // 是否已经调用了
                $length = $this->channel->length();
                if($length > 0) {
                    return $this->pop();
                }
            }
        }
    }

    /**
     * @param int $num
     * @param callable $callable
     * @throws Exception
     */
    protected function build(int $num, $callable = '') {
        if($callable instanceof \Closure) {
            $callFunction = $callable;
        }else {
            $callFunction = \Swoolefy\Core\Swfy::getAppConf()['components'][$this->poolName];
        }
        for($i=0; $i<$num; $i++) {
            $obj = call_user_func($callFunction, $this->poolName);
            if(!is_object($obj)) {
                throw new \Exception("Components of {$this->poolName} must return object");
            }
            $obj->objExpireTime = time() + ($this->liveTime) + rand(1,10);
            $this->channel->push($obj, $this->pushTimeout);
        }
    }

    /**
     * @return mixed|null
     */
    protected function pop() {
        $startTime = time();
        while($obj = $this->channel->pop($this->popTimeout)) {
            if(isset($obj->objExpireTime) && time() > $obj->objExpireTime) {
                //re build
                $this->build(1);
                if(time() - $startTime > 1) {
                    $isTimeOut = true;
                    break;
                }
            }else {
                break;
            }
        }

        if($obj === false || (isset($isTimeOut))) {
            unset($obj);
            return null;
        }

        return $obj;
    }
}