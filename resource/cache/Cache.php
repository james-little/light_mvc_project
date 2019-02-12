<?php
/**
 * Copyright 2016 Koketsu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * cache class
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache
 * @version 1.0
 **/
namespace lightmvc\resource\cache;

use lightmvc\Application;
use lightmvc\ClassLoader;
use lightmvc\exception\CacheException;
use lightmvc\exception\ExceptionCode;

class Cache
{

    protected static $_instance;
    private $adapter;

    const TYPE_MEMCACHED = 1;
    const TYPE_APC       = 2;
    const TYPE_REDIS     = 3;

    /**
     * __construct
     */
    protected function __construct()
    {
        $this->bindCacheAdapter();
    }
    /**
     * singleton
     * @throws CacheException
     */
    public static function getInstance()
    {
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
    protected function bindCacheAdapter()
    {
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
    private function getCacheAdapter($type)
    {
        $adapter = null;
        switch ($type) {
            case self::TYPE_MEMCACHED:
                //                 $adapter = ClassLoader::loadClass('\lightmvc\resource\cache\adapter\AdapterMemcache');
                $adapter = ClassLoader::loadClass('\lightmvc\resource\cache\adapter\AdapterMemcached');
                break;
            case self::TYPE_APC:
                $adapter = ClassLoader::loadClass('\lightmvc\resource\cache\adapter\AdapterApc');
                break;
            case self::TYPE_REDIS:
                $adapter = ClassLoader::loadClass('\lightmvc\resource\cache\adapter\AdapterRedis');
                break;
            default:
                throw new CacheException(
                    'specified adapter not supported yet: ' . $adapter,
                    ExceptionCode::CACHE_NOT_SUPPORT
                );
        }
        return $adapter;
    }
    /**
     * get cache config
     * @throws AppException
     */
    private function getCacheConfig()
    {
        $cache_config = Application::getConfigByKey('cache');
        if (empty($cache_config)) {
            $cache_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'cache.php';
            if (!is_file($cache_config)) {
                throw new CacheException(
                    'cache config file not exist: ' . $cache_config,
                    ExceptionCode::CACHE_CONFIG_NOT_EXIST
                );
            }
            $cache_config = include $cache_config;
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
    public function set($key, $value, $expire_time)
    {
        if (!$this->adapter) {
            return false;
        }
        return $this->adapter->set($key, $value, $expire_time);
    }
    /**
     * set value as hash by key
     * @param string $key
     * @param string $key1
     * @param string $val
     * @param int $expire_time expire time
     * @return boolean
     */
    public function setH($key, $key1, $val, $expire_time = -1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->setH($key, $key1, $val, $expire_time);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param array $data_array
     * @param int $expire_time expire time
     * @return boolean
     */
    public function setHM($key, $data_array, $expire_time = -1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->setHM($key, $data_array, $expire_time);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * get value by key from cache server
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->adapter) {
            return false;
        }
        return $this->adapter->get($key);
    }
    /**
     * get value by key list from cache server
     * @param array $key_list
     * @return mixed
     */
    public function getM($key_list)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->getM($key_list);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param string $key1
     * @return string
     */
    public function getH($key, $key1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->getH($key, $key1);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param array $field_list
     * @return array
     */
    public function getHM($key, $field_list)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->getHM($key, $field_list);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * delete value from cache server by key
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        if (!$this->adapter) {
            return false;
        }
        return $this->adapter->delete($key);
    }
    /**
     * delete value from cache
     * @param string $key
     * @param string $key1
     * @return boolean
     */
    public function deleteH($key, $key1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->deleteH($key, $key1);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * replace value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl  expire time
     * @return boolean
     */
    public function replace($key, $val, $ttl = -1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->replace($key, $val, 0, $ttl);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * increase some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function increment($key, $delta = 1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->increment($key, $delta);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * increase some value to specified value by key(Hash)
     * @param string $key
     * @param string $key1
     * @param int $step
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function incrementH($key, $key1, $step = 1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->incrementH($key, $key1, $step);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * decrease some value to specified value by key
     * @param string $key
     * @param string $delta
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function decrement($key, $delta = 1)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->decrement($key, $delta);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key)
    {
        if (!$this->adapter) {
            return false;
        }
        try {
            $val = $this->adapter->isKeyExist($key);
        } catch (CacheException $e) {
            return false;
        }
        return $val;
    }
    /**
     * flush
     * @return boolean
     */
    public function flush()
    {
        if (!$this->adapter) {
            return false;
        }
        return $this->adapter->flush();
    }
}
