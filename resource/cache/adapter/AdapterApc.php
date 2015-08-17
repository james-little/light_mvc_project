<?php
/**
 * APC adapter for cache
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache\adapter
 * @version 1.0
 **/
namespace resource\cache\adapter;

use resource\cache\adapter\CacheAdapterInterface;

class AdapterApc implements CacheAdapterInterface {

    private $_default_ttl = 0;

    /**
     * get cache connection
     * @see resource\cache\adapter.CacheAdapterInterface::getConnection()
     */
    public function getConnection() {
        return $this;
    }
    /**
     * (non-PHPdoc)
     * @see resource\cache\adapter.CacheAdapterInterface::bindConnection()
     */
    public function bindConnection($connection) {
        return $this;
    }
    /**
     * apply config
     * @param array $config
     */
    public function applyConfig($config) {
        if (empty($config)) return;
        $this->_default_ttl = empty($config['default_ttl']) ? 0 : $config['default_ttl'];
    }
    /**
     * set value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl expire time
     * @return boolean
     */
    public function set($key, $val, $ttl = 0) {
        if ($ttl < 0) {
            $ttl = $this->_default_ttl;
        }
        return apc_store($key, $val, $ttl);
    }

    /**
     * get cache value by key
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        $result = apc_fetch($key);
        return $result === false ? null : $result;
    }
    /**
     * replace value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl  expire time
     * @return boolean
     */
    public function replace($key, $val, $ttl = -1) {
        $this->delete($key);
        return $this->set($key, $val, $ttl);
    }
    /**
     * increase some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function increment($key, $delta = 1) {
        return apc_inc($key, $delta);
    }
    /**
     * decrease some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function decrement($key, $delta = 1) {
        return apc_dec($key, $delta);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        return apc_delete($key);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     */
    public function isKeyExist($key) {
        return apc_exists($key);
    }
    /**
     * flush cache server
     * @return boolean
     */
    public function flush() {
        return apc_clear_cache();
    }
}