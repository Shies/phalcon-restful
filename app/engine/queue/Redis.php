<?php
namespace Engine\Queue;
class Redis
{
    private $_redis;

    private $_parameters;

    private $_tb_prefix = 'QUEUE_TABLE_';

    //存定义存储表的数量,系统会随机分配存储
    private $_tb_num = 2;

    //临时存储表
    private $_tb_tmp = 'TMP_TABLE';

    /**
     * Phalcon\Queue\Redis
     *
     * @param array options
     * @throws Exception
     */
    public function __construct($options = null)
    {
        if (!is_array($options)) {
            $parameters = [];
        } else {
            $parameters = $options;
        }

        if (!isset($parameters["host"])) {
            $parameters["host"] = "127.0.0.1";
        }
        if (!isset($parameters["port"])) {
            $parameters["port"] = "6397";
        }
        if (!isset($parameters["persistent"])) {
            $parameters["persistent"] = false;
        }
        $this->_parameters = $parameters;
        $this->_redis = new \Redis();

        if ($parameters["persistent"]) {
            $this->_redis->pconnect($parameters["host"], $parameters["port"]);
        } else {
            $this->_redis->connect($parameters["host"], $parameters["port"]);
        }

        if (!$this->_redis) {
            throw new Exception("Could not connect to the Redisd server " . $parameters["host"] . ":" . $parameters["port"]);
        }
        if (isset($parameters["auth"])) {
            $this->_redis->auth($parameters["auth"]);
            if (!$this->_redis) {
                throw new Exception("Failed to authenticate with the Redisd server");
            }
        }
    }

    /**
     * 入列
     * @param unknown $value
     * @return int
     */
    public function push($key, $value)
    {
        if (empty($key)) {
            throw new \Exception('The key cannot for null');
        }

        try {
            // $this->_tb_prefix . rand(1, $this->_tb_num)
            return $this->_redis->lPush($key, $value);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 取得所有的list key(表)
     */
    public function scan($key)
    {
        /*
        $list_key = array();
        for ($i = 1; $i <= $this->_tb_num; $i++) {
            $list_key[] = $this->_tb_prefix . $i;
        }
        return $list_key;
        */

        if (empty($key)) {
            throw new \Exception('The key cannot for null');
        }

        $find  = [];
        $len = $this->_redis->lLen($key); // get this list total number
        while ($len)
        {
            $find[] = $this->get($key);
            $len--;
        }

        return $find;
    }

    /**
     * 出列
     * @param unknown $key
     * @param $time
     * @return
     */
    public function pop($key, $time)
    {
        if (empty($key)) {
            throw new \Exception('The key cannot for null');
        }

        try {
            if ($result = $this->_redis->brPop($key, $time)) {
                return $result[1];
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 向队列中插入一串信息
     * @return mixed
     * @internal param $message
     */
    public function puts()
    {
        $params = func_get_args();
		
		// get params is value for key
        if (empty($params[0])) {
            throw new \Exception('The key cannot for null');
        }
		
		// $this->_tb_prefix . rand(1, $this->_tb_num)
        $message_array = array_merge(array(), $params);
        return call_user_func_array(array($this->_redis, 'lPush'), $message_array);
    }

    /**
     * 从队列顶部获取一条记录
     * @return mixed
     */
    public function get($key)
    {
        if (empty($key)) {
            throw new \Exception('The key cannot for null');
        }

        // $this->_tb_prefix . rand(1, $this->_tb_num)
        return $this->_redis->lPop($key);
    }

    /**
     * 选择数据库，可以用于区分不同队列
     * @param $database
     * @internal param $database
     */
    public function select($database)
    {
        $this->_redis->select($database);
    }

    /**
     * 获取某一位置的值，不会删除该位置的值
     * @param $pos
     * @return mixed
     */
    public function view($key, $pos)
    {
        if (empty($key)) {
            throw new \Exception('The key cannot for null');
        }

        // $this->_tb_prefix . rand(1, $this->_tb_num)
        return $this->_redis->lGet($key, $pos);
    }

    /**
     * 获得队列状态，即目前队列中的消息数量
     * @return mixed
     */
    public function size($key)
    {
        if (empty($key)) {
            throw new \Exception('The key cannot for null');
        }

        // $this->_tb_prefix . rand(1, $this->_tb_num)
        return $this->_redis->lSize($key);
    }


    /* *
     * 删除当前队列元素, 即目前队列中的消息
     *
     * @return boolean  true
     * @author ___Shies
     */
    public function remove($key)
    {
        if (empty($key)) {
			throw new \Exception('The key cannot for null');
		}
		
		return $this->_redis->del($key);
    }


    /**
     * 清空,暂时无用
     */
    public function clear()
    {
        $this->_redis->flushAll();
    }

}