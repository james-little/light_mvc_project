<?php
namespace resource\cache\adapter;

/**
 * memcached adapter for cache
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache\adapter
 * @version 1.0
 **/

use resource\cache\adapter\CacheAdapterInterface,
    exception\CacheException,
    exception\ExceptionCode,
    info\InfoCollector,
    \Memcached;

class AdapterMemcached implements CacheAdapterInterface {

    protected $_memcached;
    private $default_ttl = 0;
    private $poll_timeout = 3000;
    private $connection_timeout = 5000;
    private $retry_timeout = 5000;
    private $hosts;


    /**
     * get memcache connection
     * @return \Memcache
     */
    public function getConnection() {
        if (!extension_loaded('memcached')) {
            return null;
        }
        $memcached = new Memcached();
        $memcached->setOption(Memcached::OPT_COMPRESSION, false);
        $memcached->setOption(Memcached::OPT_NO_BLOCK, true);
        $memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
        $memcached = $this->setConfigValue($memcached);
        $memcached = $this->addServers($memcached, $this->hosts);
        return $memcached;
    }
    /**
     * bind cache connection
     * @throws CacheException
     */
    public function bindConnection($connection) {
        if (!$connection instanceof \Memcached) {
            throw new CacheException(
                'connection type not match. needed: Memecached, given: '. get_class($connection),
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        $this->_memcached = $connection;
    }
    /**
     * apply config
     * @param array $config
     * @throws CacheException
     */
    public function applyConfig($config) {
        if (empty($config)) return;
        $hosts_list = empty($config['hosts']) ? array() : $config['hosts'];
        if (empty($hosts_list)) {
            throw new CacheException('hosts config empty',
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        foreach ($hosts_list as $hosts) {
            $host_array = array();
            $host_array[0] = $hosts['host'];
            $host_array[1] = $hosts['port'];
            $host_array[2] = $hosts['weight'];
            $this->hosts[] = $host_array;
        }
        unset($hosts_list);
        $this->default_ttl = empty($config['default_ttl']) ? -1 : $config['default_ttl'];
        $this->retry_timeout = empty($config['retry_timeout']) ? 2000 : $config['retry_timeout'];
        $this->connection_timeout = empty($config['connection_timeout']) ? 5000 : $config['connection_timeout'];
        $this->poll_timeout = empty($config['poll_timeout']) ? 5000 : $config['poll_timeout'];
    }
    /**
     * set config value
     * @param Memcached $memcached
     * @return Memcached
     */
    private function setConfigValue($memcached) {
        $memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, $this->connection_timeout);
        $memcached->setOption(Memcached::OPT_RETRY_TIMEOUT, $this->retry_timeout);
        $memcached->setOption(Memcached::OPT_POLL_TIMEOUT, $this->poll_timeout);
        return $memcached;
    }
    /**
     * add cache server to cache server group
     * @param Memcached $memcached
     * @param String $host
     * @param String $port
     * @param int $weight
     * @throws CacheException
     * @return Memcached
     */
    public function addServer($memcached, $host, $port, $weight = null) {
        if (empty($memcached)) {
            throw new CacheException('connection object is empty',
                    ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        $memcached->addServer($host, $port, $weight);
        return $memcached;
    }
    /**
     * add servers
     * @param array $server_list
     *     example:
     *         $servers = array(
     *              array('mem1.domain.com', 11211, 33),
     *              array('mem2.domain.com', 11211, 67)
     *          );
     * @throws CacheException
     */
    public function addServers($memcached, $server_list) {
        if (empty($memcached)) {
            throw new CacheException('connection object is empty',
                    ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        $memcached->addServers($server_list);
        return $memcached;
    }
    /**
     * get cache value by key
     * @param string $key
     * @return string
     */
    public function get($key) {
        if (!$this->_memcached) return false;
        $result = $this->_memcached->get($key);
        if(!$result && $this->_memcached->getResultCode() == Memcached::RES_NOTFOUND) {
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
        if (!$this->_memcached) return false;
        $val = convert_string($val);
        if ($expire_time < 0) {
            $expire_time = $this->default_ttl;
        }
        return $this->_memcached->add($key, $val, $expire_time);
    }
    /**
     * set value by key
     * @param string $key
     * @param string $val
     * @param int $expire_time expire time
     * @return boolean
     */
    public function set($key, $val, $expire_time = -1) {
        if (!$this->_memcached) return false;
        if ($expire_time < 0) {
            $expire_time = $this->default_ttl;
        }
        $result = $this->_memcached->set($key, convert_string($val), $expire_time);
        if(!$result && $this->_memcached->getResultCode() == Memcached::RES_WRITE_FAILURE) {
            __add_info(
                'key write to server failed: ' . $key .
                '. Message:' . $this->_memcached->getResultMessage()
            , InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_ERR);
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
        if (!$this->_memcached) return false;
        if ($expire_time < 0) {
            $expire_time = $this->default_ttl;
        }
        $result = $this->_memcached->replace($key, convert_string($val), $expire_time);
        if(!$result && $this->_memcached->getResultCode() == Memcached::RES_NOTSTORED) {
            __add_info(
                'key not exist in this server: ' . $key .
                '. Message:' . $this->_memcached->getResultMessage()
            , InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_ERR);
        }
        return result;
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
        if (!$this->_memcached) return false;
        return $this->_memcached->increment($key, $step);
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
        if (!$this->_memcached) return false;
        return $this->_memcached->decrement($key, $step, 0);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        if (!$this->_memcached) return false;
        return $this->_memcached->delete($key);
    }
    /**
     * set new expire time to key
     * @param string $key
     * @param int $expire_time
     * @return boolean
     */
    public function touch($key, $expire_time = -1) {
        if (!$this->_memcached) return false;
        if ($expire_time < 0) {
            $expire_time = $this->default_ttl;
        }
        return $this->_memcached->touch($key, $expire_time);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key) {
        if (!$this->_memcached) return false;
        return $this->get($key) !== null;
    }
    /**
     * flush cache server
     * @return boolean
     */
    public function flush() {
        if (!$this->_memcached) return false;
        return $this->_memcached->flush();
    }

}