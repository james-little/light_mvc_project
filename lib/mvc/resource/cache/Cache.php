<?php
namespace resource\cache;

/**
 * cache class
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache
 * @version 1.0
 **/
use info\InfoCollector,
    resource\ResourcePool,
    exception\CacheException,
    exception\ExceptionCode;

class Cache {

    protected static $_instance;
    private $adapter;
    
    const TYPE_MEMCACHED = 'memcached';
    const TYPE_APC = 'apc';
    
    /**
     * __construct
     */
    protected function __construct() {}

    /**
     * bind cache
     * @param string $type
     * @param array $config
     * @throws CacheException
     */
    protected function bindCacheAdapter($type) {

        $cache_config = $this->getCacheConfig($type);
        $resource_pool = ResourcePool::getInstance();
        $resource_key = $resource_pool->getResourceKey('cache_' . $type, $cache_config);
        $adapter = $resource_pool->getResource('cache', $resource_key);
        if (!$adapter) {
            $adapter = $this->getCacheAdapter($type);
            $adapter->bindConnection($adapter->getConnection());
            $adapter->applyConfig($cache_config);
            $resource_pool->registerResource('cache', $resource_key, $adapter);
        }
        $this->adapter = $adapter;
        return $this;
    }
    /**
     * get cache adapter
     * @param string $type
     * @throws CacheException
     * @return Ambigous <NULL, boolean, multitype:>
     */
    private function getCacheAdapter($type) {
        $adapter = null;
        switch ($type) {
            case 'memcached':
                $adapter = \ClassLoader::loadClass('\resource\cache\adapter\AdapterMemcached');
                break;
            case 'apc':
                $adapter = \ClassLoader::loadClass('\resource\cache\adapter\AdapterApc');
                break;
            default:
                throw new CacheException(__message('specified adapter not supported yet: %s', array($adapter)),
                ExceptionCode::CACHE_NOT_SUPPORT);
        }
        return $adapter;
    }
    /**
     * get cache config
     * @throws AppException
     */
    private function getCacheConfig($type) {
        $cache_config = \AppRuntimeContext::getInstance()->getData('cache_config');
        if (empty($cache_config)) {
            $cache_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DIRECTORY_SEPARATOR . 'cache.php';
            if (!file_exists($cache_config)) {
                throw new AppException(
                        __message('cache config file not exist: %s',array($cache_config)),
                        ExceptionCode::BUSINESS_CACHE_CONFIG_NOT_EXIST);
            }
            $cache_config = include($cache_config);
            \AppRuntimeContext::getInstance()->setData('cache_config', $cache_config);
        }
        switch ($type) {
            case 'memcached':
                $cache_config = isset($cache_config['memcached']) ? $cache_config['memcached'] : array();
                break;
            case 'apc':
                $cache_config = isset($cache_config['apc']) ? $cache_config['apc'] : array();
                break;
            default:
                return array();
        }
        return $cache_config;
    }
    /**
     * singleton
     */
    public static function getInstance($type) {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        self::$_instance->bindCacheAdapter($type);
        return self::$_instance;
    }
    /**
     * set key-value set to cache server
     * @param string $key
     * @param mixed $value
     * @param int $expire_time. expire time(in seconds)
     * @throws CacheException
     */
    public function set($key, $value, $expire_time) {
        if (!$this->adapter) throw new CacheException(__message('adapter not set'));
        return $this->adapter->set($key, $value, $expire_time);
    }
    /**
     * get value by key from cache server
     * @param string $key
     * @throws CacheException
     */
    public function get($key) {
        if (!$this->adapter) throw new CacheException(__message('adapter not set'));
        return $this->adapter->get($key);
    }
    /**
     * delete value from cache server by key
     * @param string $key
     * @throws CacheException
     */
    public function delete($key) {
        if (!$this->adapter) throw new CacheException(__message('adapter not set'));
        return $this->adapter->delete($key);
    }
	/**
     * replace value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl  expire time
     * @return boolean
     */
    public function replace($key, $val, $ttl = -1) {
    	if (!$this->adapter) throw new CacheException(__message('adapter not set'));
        return $this->adapter->replace($key, $val, 0, $ttl);
    }
    /**
     * increase some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return mixed|false
     */
    public function increment($key, $delta = 1) {
    	if (!$this->adapter) throw new CacheException(__message('adapter not set'));
        return $this->adapter->increment($key, $delta);
    }
    /**
     * decrease some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return mixed|false
     */
    public function decrement($key, $delta = 1) {
    	if (!$this->adapter) throw new CacheException(__message('adapter not set'));
        return $this->adapter->decrement($key, $delta);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     */
    public function isKeyExist($key) {
    	if (!$this->adapter) throw new CacheException(__message('adapter not set'));
        return $this->adapter->isKeyExist($key);
    }
}