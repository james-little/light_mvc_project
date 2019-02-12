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
 * memcached adapter for cache
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache\adapter
 * @version 1.0
 **/
namespace lightmvc\resource\cache\adapter;

use lightmvc\exception\CacheException;
use lightmvc\exception\ExceptionCode;
use lightmvc\info\InfoCollector;
use Memcached;
use lightmvc\resource\cache\adapter\CacheAdapterInterface;
use lightmvc\resource\ResourcePool;

class AdapterMemcached implements CacheAdapterInterface
{

    protected $_memcached;
    private $config;

    /**
     * get memcache connection
     * @return Memcached
     */
    public function getConnection()
    {
        if (!extension_loaded('memcached')) {
            return null;
        }
        $resource_type = 'memcached';
        $resource_pool = ResourcePool::getInstance();
        $resource_key  = $resource_pool->getResourceKey($this->config);
        $memcached     = $resource_pool->getResource($resource_type, $resource_key);
        if ($memcached) {
            return $memcached;
        }
        $memcached = new Memcached();
        $memcached = $this->initialize($memcached);
        $resource_pool->registerResource($resource_type, $resource_key, $memcached);
        return $memcached;
    }
    /**
     * bind cache connection
     * @throws CacheException
     */
    public function bindConnection($connection)
    {
        if (!$connection instanceof Memcached) {
            throw new CacheException(
                'connection type not match. needed: Memecached, given: ' . get_class($connection),
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
        }
        $this->_memcached = $connection;
    }
    /**
     * apply config
     * @param array $config
     * @throws CacheException
     */
    public function applyConfig($config)
    {
        if (empty($config)) {
            throw new CacheException(
                'hosts config empty',
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
        }
        $hosts_list = empty($config['hosts']) ? [] : $config['hosts'];
        if (empty($hosts_list)) {
            throw new CacheException(
                'hosts config empty',
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
        }
        $memcached_config = [];
        foreach ($hosts_list as $hosts) {
            $host_array                  = [];
            $host_array[0]               = $hosts['host'];
            $host_array[1]               = $hosts['port'];
            $host_array[2]               = $hosts['weight'];
            $memcached_config['hosts'][] = $host_array;
        }
        unset($hosts_list);
        $memcached_config['default_ttl']        = empty($config['default_ttl']) ? -1 : $config['default_ttl'];
        $memcached_config['retry_timeout']      = empty($config['retry_timeout']) ? 2000 : $config['retry_timeout'] * 1000;
        $memcached_config['connection_timeout'] = empty($config['connection_timeout']) ? 5000 : $config['connection_timeout'] * 1000;
        $memcached_config['poll_timeout']       = empty($config['poll_timeout']) ? 5000 : $config['poll_timeout'] * 1000;
        $this->config                           = $memcached_config;
    }
    /**
     * set config value
     * @param Memcached $memcached
     * @return Memcached
     */
    private function initialize($memcached)
    {
        $memcached->setOption(Memcached::OPT_COMPRESSION, false);
        $memcached->setOption(Memcached::OPT_NO_BLOCK, true);
        $memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
        $memcached = $this->addServers($memcached, $this->config['hosts']);
        $memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, $this->config['connection_timeout']);
        $memcached->setOption(Memcached::OPT_RETRY_TIMEOUT, $this->config['retry_timeout']);
        $memcached->setOption(Memcached::OPT_POLL_TIMEOUT, $this->config['poll_timeout']);
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
    public function addServer($memcached, $host, $port, $weight = null)
    {
        if (empty($memcached)) {
            throw new CacheException(
                'connection object is empty',
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
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
    public function addServers($memcached, $server_list)
    {
        if (empty($memcached)) {
            throw new CacheException(
                'connection object is empty',
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
        }
        $memcached->addServers($server_list);
        return $memcached;
    }
    /**
     * get cache value by key
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        if (!$this->_memcached) {
            return false;
        }

        $result = $this->_memcached->get($key);
        if (!$result && $this->_memcached->getResultCode() == Memcached::RES_NOTFOUND) {
            $result = null;
        }
        return $result;
    }
    /**
     * get cache value by key list
     * @param array $key
     * @return array
     */
    public function getM($key_list)
    {
        throw new CacheException(
            'not supported operation: multiple get',
            ExceptionCode::CACHE_NOT_SUPPORT_OPERATION
        );
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param string $key1
     * @return string
     */
    public function getH($key, $key1)
    {
        throw new CacheException(
            'not supported operation: multiple get',
            ExceptionCode::CACHE_NOT_SUPPORT_OPERATION
        );
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param array $key1_array
     * @return array
     */
    public function getHM($key, $key1_array)
    {
        throw new CacheException(
            'not supported operation: multiple get',
            ExceptionCode::CACHE_NOT_SUPPORT_OPERATION
        );
    }
    /**
     * add value to cache by key
     * @param string $key
     * @param string $val
     * @param int $expire_time expire time
     * @return boolean
     */
    public function add($key, $val, $expire_time = -1)
    {
        if (!$this->_memcached) {
            return false;
        }

        $val = convert_string($val);
        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
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
    public function set($key, $val, $expire_time = -1)
    {
        if (!$this->_memcached) {
            return false;
        }

        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
        }
        $result = $this->_memcached->set($key, convert_string($val), $expire_time);
        if (!$result && $this->_memcached->getResultCode() == Memcached::RES_WRITE_FAILURE) {
            __add_info(
                'key write to server failed: ' . $key .
                '. Message:' . $this->_memcached->getResultMessage(),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_ERR
            );
        }
        return $result;
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
        throw new CacheException(
            'not supported operation: multiple get',
            ExceptionCode::CACHE_NOT_SUPPORT_OPERATION
        );
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
        throw new CacheException(
            'not supported operation: multiple get',
            ExceptionCode::CACHE_NOT_SUPPORT_OPERATION
        );
    }
    /**
     * replace value by key
     * @param string $key
     * @param string $val
     * @param int $expire_time  expire time
     * @return boolean
     */
    public function replace($key, $val, $expire_time = -1)
    {
        if (!$this->_memcached) {
            return false;
        }

        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
        }
        $result = $this->_memcached->replace($key, convert_string($val), $expire_time);
        if (!$result && $this->_memcached->getResultCode() == Memcached::RES_NOTSTORED) {
            __add_info(
                'key not exist in this server: ' . $key .
                '. Message:' . $this->_memcached->getResultMessage(),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_ERR
            );
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
    public function increment($key, $step = 1)
    {
        if (!$this->_memcached) {
            return false;
        }

        return $this->_memcached->increment($key, $step);
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
        throw new CacheException(
            'not supported operation: multiple get',
            ExceptionCode::CACHE_NOT_SUPPORT_OPERATION
        );
    }
    /**
     * decrease some value to specified value by key
     * @param string $key
     * @param int $step
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function decrement($key, $step = 1)
    {
        if (!$this->_memcached) {
            return false;
        }
        return $this->_memcached->decrement($key, $step, 0);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        if (!$this->_memcached) {
            return false;
        }
        return $this->_memcached->delete($key);
    }
    /**
     * delete value from cache
     * @param string $key
     * @param string $key1
     * @return boolean
     */
    public function deleteH($key, $key1)
    {
        throw new CacheException(
            'not supported operation: multiple get',
            ExceptionCode::CACHE_NOT_SUPPORT_OPERATION
        );
    }
    /**
     * set new expire time to key
     * @param string $key
     * @param int $expire_time
     * @return boolean
     */
    public function touch($key, $expire_time = -1)
    {
        if (!$this->_memcached) {
            return false;
        }
        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
        }
        return $this->_memcached->touch($key, $expire_time);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key)
    {
        if (!$this->_memcached) {
            return false;
        }
        return $this->get($key) !== null;
    }
    /**
     * flush cache server
     * @return boolean
     */
    public function flush()
    {
        if (!$this->_memcached) {
            return false;
        }
        return $this->_memcached->flush();
    }
}
