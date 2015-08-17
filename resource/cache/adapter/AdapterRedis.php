<?php
namespace resource\cache\adapter;

/**
 * redis adapter for cache
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache\adapter
 * @version 1.0
 **/

use resource\cache\adapter\CacheAdapterInterface,
    exception\CacheException,
    exception\ExceptionCode,
    info\InfoCollector,
    \Redis;

class AdapterRedis implements CacheAdapterInterface {

    protected $_redis;
    private $default_ttl = 0;
    private $retry_timeout = 2;
    private $timeout = 2;
    private $prefix;
    private $dbname;
    private $passwd;
    private $host;
    private $port;

    /**
     * get memcache connection
     * @return \Memcache
     */
    public function getConnection() {
        if (!extension_loaded('redis')) {
            return null;
        }
        $redis = new Redis();
        return $redis;
    }
    /**
     * bind cache connection
     * @throws CacheException
     */
    public function bindConnection($connection) {
        if (!$connection instanceof \Redis) {
            throw new CacheException(
                'connection type not match. needed: Redis, given: ' . get_class($connection),
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        $this->_redis = $this->initialize($connection);
    }
    /**
     * initialize redis
     * @param  Redis $redis
     * @return Redis
     */
    private function initialize($redis) {
        if($this->port) {
            if($redis->pconnect($this->host, $this->port, $this->timeout, NULL, $this->retry_timeout) === false) {
                throw new CacheException(
                    'redis connection failed: ' . $this->host . ':' . $this->port,
                    ExceptionCode::REDIS_CONNECTION_ERROR);
            }
        } else {
            if($redis->pconnect($this->host) === false) {
                // unix socket
                throw new CacheException(
                    'redis connection failed: ' . $this->host,
                    ExceptionCode::REDIS_CONNECTION_ERROR);
            }
        }
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        $redis->select($this->dbname);
        if($this->prefix) {
            $redis->setOption(Redis::OPT_PREFIX, $this->prefix);
        }
        if($this->passwd && $redis->auth($this->passwd) === false) {
            throw new CacheException(
                'redis auth failed: ' . $this->host . ':' . $this->passwd,
                ExceptionCode::REDIS_AUTH_ERROR);
        }
        return $redis;
    }

    /**
     * apply config
     * @param array $config
     * @throws CacheException
     */
    public function applyConfig($config) {
        if (empty($config)) return;
        if(empty($config['host'])) {
            throw new CacheException('host empty',
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        $this->host = $config['host'];
        if(substr($this->host, 0, 1) != '/') {
            // if host is not unix socket, then port is needed
            $this->port = empty($config['port']) ? 6379 : $config['port'];
        }
        $this->dbname = empty($config['dbname']) ? 0 : $config['dbname'];
        if(!empty($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }
        if(!empty($config['password'])) {
            $this->passwd = $config['password'];
        }
        if(!empty($config['default_ttl'])) {
            $this->default_ttl = $config['default_ttl'];
        }
        if(!empty($config['connection_timeout'])) {
            $this->connection_timeout = $config['connection_timeout'] * 1000;
        }
        if(!empty($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
        if(!empty($config['retry_timeout'])) {
            $this->retry_timeout = $config['retry_timeout'];
        }
    }
    /**
     * get cache value by key
     * @param string $key
     * @return string
     */
    public function get($key) {
        if (!$this->_redis) return false;
        $result = $this->_redis->get($key);
        if($result === false) {
            $result = null;
        }
        return $result;
    }
    /**
     * add value to cache by key
     * @param string $key
     * @param string $val
     * @param int $expire_time expire time
     * @return boolean
     */
    public function add($key, $val, $expire_time = -1) {
        return $this->set($key, $val, $expire_time);
    }
    /**
     * set value by key
     * @param string $key
     * @param string $val
     * @param int $expire_time expire time
     * @return boolean
     */
    public function set($key, $val, $expire_time = -1) {
        if (!$this->_redis) return false;
        if ($expire_time < 0) {
            $expire_time = $this->default_ttl;
        }
        $result = $this->_redis->setex($key, $expire_time, convert_string($val));
        if($result === false) {
            __add_info('key write to server failed: ' . $key,
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_ERR);
        }
        return $result;
    }
    /**
     * replace value by key
     * @param string $key
     * @param string $val
     * @param int $expire_time  expire time
     * @return boolean
     */
    public function replace($key, $val, $expire_time = -1) {
        return $this->set($key, $val, $expire_time);
    }
    /**
     * increase some value to specified value by key
     * @param string $key
     * @param int $step
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function increment($key, $step = 1) {
        if (!$this->_redis) return false;
        return $this->_redis->incrBy($key, $step);
    }
    /**
     * decrease some value to specified value by key
     * @param string $key
     * @param int $step
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function decrement($key, $step = 1) {
        if (!$this->_redis) return false;
        return $this->_redis->decrBy($key, $step);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        if (!$this->_redis) return false;
        return $this->_redis->delete($key);
    }
    /**
     * set new expire time to key
     * @param string $key
     * @param int $expire_time
     * @return boolean
     */
    public function touch($key, $expire_time = -1) {
        if (!$this->_redis) return false;
        if ($expire_time < 0) {
            $expire_time = $this->default_ttl;
        }
        return $this->_redis->setTimeout($key, $expire_time);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key) {
        if (!$this->_redis) return false;
        return $this->get($key) !== null;
    }
    /**
     * flush cache server
     * @return boolean
     */
    public function flush() {
        if (!$this->_redis) return false;
        return $this->_redis->flushDB();
    }

}