<?php
namespace resource\cache;

/**
 * cache class
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache
 * @version 1.0
 **/
use Application,
    ClassLoader,
    exception\CacheException,
    exception\ExceptionCode;

class Cache {

    protected static $_instance;
    private $adapter;

    const TYPE_MEMCACHED = 1;
    const TYPE_APC = 2;
    const TYPE_REDIS = 3;

    /**
     * __construct
     */
    protected function __construct() {
        $this->bindCacheAdapter();
    }
    /**
     * singleton
     * @throws CacheException
     */
    public static function getInstance() {
        if (self::$_instance !== null) {
            return self::$_instance;
        }
        self::$_instance = new self();
        return self::$_instance;
    }
    /**
     * bind cache
     * @param array $config
     * @throws CacheException
     */
    protected function bindCacheAdapter() {
        $cache_config = $this->getCacheConfig();
        if (empty($cache_config)) {
            return $this;
        }
        $adapter = $this->getCacheAdapter($cache_config['type']);
        $adapter->applyConfig($cache_config);
        $adapter->bindConnection($adapter->getConnection());
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
            case self::TYPE_MEMCACHED:
//                 $adapter = ClassLoader::loadClass('\resource\cache\adapter\AdapterMemcache');
                $adapter = ClassLoader::loadClass('\resource\cache\adapter\AdapterMemcached');
                break;
            case self::TYPE_APC:
                $adapter = ClassLoader::loadClass('\resource\cache\adapter\AdapterApc');
                break;
            case self::TYPE_REDIS:
                $adapter = ClassLoader::loadClass('\resource\cache\adapter\AdapterRedis');
                break;
            default:
                throw new CacheException('specified adapter not supported yet: ' . $adapter,
                    ExceptionCode::CACHE_NOT_SUPPORT);
        }
        return $adapter;
    }
    /**
     * get cache config
     * @throws AppException
     */
    private function getCacheConfig() {
        $cache_config = Application::getConfigByKey('cache');
        if (empty($cache_config)) {
            $cache_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'cache.php';
            if (!is_file($cache_config)) {
                throw new CacheException(
                    'cache config file not exist: ' . $cache_config,
                    ExceptionCode::CACHE_CONFIG_NOT_EXIST);
            }
            $cache_config = include($cache_config);
            Application::setConfig('cache', $cache_config);
        }
        if (!$cache_config['enabled']) {
            return [];
        }
        $type = $cache_config['type'];
        switch ($type) {
            case self::TYPE_MEMCACHED:
                $cache_config = isset($cache_config['memcached']) ? $cache_config['memcached'] : [];
                break;
            case self::TYPE_APC:
                $cache_config = isset($cache_config['apc']) ? $cache_config['apc'] : [];
                break;
            case self::TYPE_REDIS:
                $cache_config = isset($cache_config['redis']) ? $cache_config['redis'] : [];
                break;
            default:
                return [];
        }
        $cache_config['type'] = $type;
        return $cache_config;
    }
    /**
     * set key-value set to cache server
     * @param string $key
     * @param mixed $value
     * @param int $expire_time. expire time(in seconds)
     * @return boolean
     */
    public function set($key, $value, $expire_time) {
        if (!$this->adapter) return false;
        return $this->adapter->set($key, $value, $expire_time);
    }
    /**
     * get value by key from cache server
     * @param string $key
     * @return mixed
     */
    public function get($key) {
        if (!$this->adapter) return false;
        return $this->adapter->get($key);
    }
    /**
     * delete value from cache server by key
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        if (!$this->adapter) return false;
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
        if (!$this->adapter) return false;
        return $this->adapter->replace($key, $val, 0, $ttl);
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
        if (!$this->adapter) return false;
        return $this->adapter->increment($key, $delta);
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
        if (!$this->adapter) return false;
        return $this->adapter->decrement($key, $delta);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key) {
        if (!$this->adapter) return false;
        return $this->adapter->isKeyExist($key);
    }
    /**
     * flush
     * @return boolean
     */
    public function flush() {
        if (!$this->adapter) return false;
        return $this->adapter->flush();
    }
}