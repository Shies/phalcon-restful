<?php

namespace Engine;

use Phalcon\Cache\Backend\Redis as BackendRedis;
use Phalcon\Cache\BackendInterface;
use Phalcon\Cache\Exception;

/**
 * Phalcon\Cache\Backend\Redis
 *
 * Allows to cache output fragments, PHP data or raw data to a redis backend
 *
 * This adapter uses the special redis key "_PHCR" to store all the keys internally used by the adapter
 *
 *<code>
 *
 * // Cache data for 2 days
 * $frontCache = new \Phalcon\Cache\Frontend\Data(array(
 *    "lifetime" => 172800
 * ));
 *
 * //Create the Cache setting redis connection options
 * $cache = new Phalcon\Cache\Backend\Redis($frontCache, array(
 *        'host' => 'localhost',
 *        'port' => 6379,
 *        'auth' => 'foobared',
 *    'persistent' => false
 * ));
 *
 * //Cache arbitrary data
 * $cache->save('my-data', array(1, 2, 3, 4, 5));
 *
 * //Get data
 * $data = $cache->get('my-data');
 *
 *</code>
 */
class Redis extends BackendRedis
{

    protected $_redis = null;
    protected $_prefix = "";
    protected $_type = "";

    /**
     * Phalcon\Cache\Backend\Redis constructor
     *
     * @param  \Phalcon\Cache\FrontendInterface $frontend
     * @param  array $options
     * @throws \Phalcon\Cache\Exception
     */
    public function __construct($frontend, $options = null)
    {
        if (isset($options["prefix"]) || !empty($options["prefix"])) {
            $this->_prefix = $options["prefix"];
        }

        parent::__construct($frontend, $options);
    }


    /**
     * @param $name
     * @param $prefix
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function hset($name, $prefix, $data)
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }
        if (!is_array($data) || empty($data)) return false;
        foreach ($data as $key => $value) {

            if ($value[0] == 'exp') {
                $value[1] = str_replace(' ', '', $value[1]);
                preg_match('/^[A-Za-z_]+([+-]\d+(\.\d+)?)$/', $value[1], $matches);
                if (is_numeric($matches[1])) {
                    $this->hIncrBy($name, $prefix, $key, $matches[1]);
                }
                unset($data[$key]);
            }
        }
        $this->_type = $prefix;
        if (count($data) == 1) {
            $redis->hset($this->_key($name), key($data), current($data));
        } elseif (count($data) > 1) {
            $redis->hMset($this->_key($name), $data);
        }
    }


    /**
     * @param $name
     * @param $prefix
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public function hget($name, $prefix, $key = null)
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }
        $this->_type = $prefix;
        if ($key == '*' || is_null($key)) {
            return $redis->hGetAll($this->_key($name));
        } elseif (strpos($key, ',') != false) {
            return $redis->hmGet($this->_key($name), explode(',', $key));
        } else {
            return $redis->hget($this->_key($name), $key);
        }
    }


    /**
     * @param $name
     * @param $prefix
     * @param null $key
     * @return bool
     * @throws Exception
     */
    public function hdel($name, $prefix, $key = null)
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        $this->_type = $prefix;
        if (is_null($key)) {
            if (is_array($name)) {
                return $redis->delete(array_walk($array, array(self, '_key')));
            } else {
                return $redis->delete($this->_key($name));
            }
        } else {
            if (is_array($name)) {
                foreach ($name as $key => $value) {
                    $redis->hdel($this->_key($name), $key);
                }
                return true;
            } else {
                return $redis->hdel($this->_key($name), $key);
            }
        }
    }

    /**
     * @param $name
     * @param $prefix
     * @param $key
     * @param int $num
     * @throws Exception
     */
    public function hIncrBy($name, $prefix, $key, $num = 1)
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }
        if ($this->hget($name, $prefix, $key) !== false) {
            $redis->hIncrByFloat($this->_key($name), $key, floatval($num));
        }
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param $name
     * @param int $num
     * @return int 　操作后的值
     * @internal param int $default 操作时的默认值
     */
    public function IncrBy($name, $num = 1)
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }
        if ($this->get('_PHCR' . $name) !== false) {
            $redis->incr('_PHCR' . $name, $num);
        } else {
            $redis->incrBy('_PHCR' . $name, $num);
        }
    }

    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param $name
     * @param int $num
     * @return int 　操作后的值
     * @internal param int $default 操作时的默认值
     */
    public function DecrBy($name, $num = 1)
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }
        if ($this->get('_PHCR' . $name) !== false) {
            $redis->decr('_PHCR' . $name, $num);
        } else {
            $redis->decrBy('_PHCR' . $name, $num);
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function clear()
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }

        return $redis->flushDB();
    }

    /**
     * Transaction start
     */
    public function multi()
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }
        return $redis->multi();
    }

    /**
     * Transaction send
     */

    public function exec()
    {
        $redis = $this->_redis;

        if (!is_object($redis)) {
            $this->_connect();
            $redis = $this->_redis;
        }
        return $redis->exec();
    }

    private function _key($str)
    {
        return $this->_prefix . $this->_type . $str;
    }
}
