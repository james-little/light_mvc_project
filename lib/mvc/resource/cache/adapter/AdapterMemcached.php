<?php
namespace resource\cache\adapter;

/**
 * memcache adapter for cache
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache\adapter
 * @version 1.0
 **/
use resource\cache\adapter\CacheAdapterInterface,
    exception\CacheException,
    exception\ExceptionCode,
    \Memcache;

class AdapterMemcached implements CacheAdapterInterface {

    protected $_memcache;
    protected $_default_ttl = 0;

    
    /**
     * get memcache connection
     * @return \Memcache
     */
    public function getConnection() {
        // default is usually true
        ini_set('memcache.allow_failover', true);
        ini_set('memcache.hash_strategy', 'consistent');
        return new Memcache();
    }
    /**
     * bind cache connection
     */
    public function bindConnection($connection) {
        if (!$connection instanceof \Memcache) {
            throw new CacheException(
                    __message('connection type not match. needed: Memecache, given: %s', array(get_class($connection))),
                    ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        $this->_memcache = $connection;
    }
    /**
     * apply config
     * @param array $config
     */
    public function applyConfig($config) {
        if (empty($config)) return;
        $hosts_list = empty($config['hosts']) ? array() : $config['hosts'];
        if (count($hosts_list)) {
            foreach ($hosts_list as $host) {
                $this->addServer($host['host'], $host['port']);
            }
        }
        $this->_default_ttl = empty($config['default_ttl']) ? -1 : $config['default_ttl'];
    }
    /**
     * add cache server to cache server group
     * @param string $host
     * @param int $port
     */
    private function addServer($host, $port) {
        if (empty($this->_memcache)) {
            throw new CacheException(__message('connection is still not binded'), ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
        }
        if ($this->_memcache->connect($host, $port)) {
            $this->_memcache->addServer($host, $port);
        } else {
            $this->_memcache->addServer($host, $port, true, 1, 1, -1, false);
        }
    }
    /**
     * get cache value by key
     * @param string $key
     * @return string
     */
    public function get($key) {
        if (!$this->_memcache) return false;
        return $this->_memcache->get($key);
    }
    /**
     * add value to cache by key
     * @param string $key
     * @param string $val
     * @param integer $ttl expire time
     * @return boolean
     */
    public function add($key, $val, $ttl = -1) {
        if (!$this->_memcache) return false;
        if ($ttl < 0) $ttl = $this->_default_ttl;
        return $this->_memcache->add($key, $val, 0, $ttl);
    }
    /**
     * set value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl expire time
     * @return boolean
     */
    public function set($key, $val, $ttl = -1) {
        if (!$this->_memcache) return false;
        if ($ttl < 0) $ttl = $this->_default_ttl;
        return $this->_memcache->set($key, $val, 0, $ttl);
    }
    /**
     * replace value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl  expire time
     * @return boolean
     */
    public function replace($key, $val, $ttl = -1) {
        if (!$this->_memcache) return false;
        if ($ttl < 0) $ttl = $this->_default_ttl;
        return $this->_memcache->replace($key, $val, 0, $ttl);
    }
    /**
     * increase some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return mixed|false
     */
    public function increment($key, $delta = 1) {
        if (!$this->_memcache) return false;
        return $this->_memcache->increment($key, $delta);
    }
    /**
     * decrease some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return mixed|false
     */
    public function decrement($key, $delta = 1) {
        if (!$this->_memcache) return false;
        return $this->_memcache->decrement($key, $delta);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        if (!$this->_memcache) return false;
        return $this->_memcache->delete($key);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     */
    public function isKeyExist($key) {
    	if (!$this->_memcache) return false;
    	return $this->get($key) !== false;
    }
}