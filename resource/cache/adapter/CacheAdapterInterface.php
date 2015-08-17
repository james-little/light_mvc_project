<?php
namespace resource\cache\adapter;

/**
 * cache adapter interface
 * =======================================================
 * defines basic operations for cache system
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @package resource\cache\adapter
 * @version 1.1
 **/
interface CacheAdapterInterface {

    /**
     * get connection
     * @return Connection Object
     */
    public function getConnection();
    /**
     * bind connection
     * @param $connection
     * @throws CacheException
     */
    public function bindConnection($connection);
    /**
     * apply cache config
     * @param array $config
     * @return void
     */
    public function applyConfig($config);
    /**
     * set value to cache
     * @param string $key
     * @param mixed $value
     * @param int $time seconds
     * @return boolean
     */
    public function set($key, $value, $time);
    /**
     * get value from cache
     * @param string $key
     * @return mixed
     */
    public function get($key);
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key);

    /**
     * increase some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function increment($key, $delta = 1);
    /**
     * decrease some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function decrement($key, $delta = 1);
    /**
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key);
    /**
     * flush cache server
     * @return boolean
     */
    public function flush();

}