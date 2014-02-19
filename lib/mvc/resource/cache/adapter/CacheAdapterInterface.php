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
	 * @return connection
	 */
	public function getConnection();
	/**
	 * bind connection
	 * @param $connection
	 */
	public function bindConnection($connection);
    /**
     * apply cache config
     * @param array $config
     */
    public function applyConfig($config);
    /**
     * set value to cache
     * @param string $key
     * @param mixed $value
     * @param int $time seconds
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
     */
    public function delete($key);

}