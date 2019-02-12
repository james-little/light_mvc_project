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
 * memcache adapter for cache
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache\adapter
 * @version 1.0
 **/
namespace lightmvc\resource\cache\adapter;

use lightmvc\exception\CacheException;
use lightmvc\exception\ExceptionCode;
use Memcache;
use lightmvc\resource\cache\adapter\CacheAdapterInterface;

class AdapterMemcache implements CacheAdapterInterface
{

    protected $_memcache;
    protected $_default_ttl = 0;

    /**
     * get memcache connection
     * @return \Memcache
     */
    public function getConnection()
    {
        // default is usually true
        ini_set('memcache.allow_failover', 0);
        ini_set('memcache.hash_strategy', 'consistent');
        return new Memcache();
    }
    /**
     * bind cache connection
     * @throws CacheException
     */
    public function bindConnection($connection)
    {
        if (!$connection instanceof \Memcache) {
            throw new CacheException(
                'connection type not match. needed: Memecache, given: ' . get_class($connection),
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
        }
        $this->_memcache = $connection;
    }
    /**
     * apply config
     * @param array $config
     * @throws CacheException
     */
    public function applyConfig($config)
    {
        if (empty($config)) {
            return;
        }

        $hosts_list = empty($config['hosts']) ? [] : $config['hosts'];
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
     * @throws CacheException
     */
    private function addServer($host, $port)
    {
        if (empty($this->_memcache)) {
            throw new CacheException('connection is still not binded', ExceptionCode::CACHE_ACCESS_OBJ_ERROR);
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
    public function get($key)
    {
        if (!$this->_memcache) {
            return false;
        }

        $result = $this->_memcache->get($key);
        return $result === false ? null : $result;
    }
    /**
     * get cache value by key
     * @param string $key
     * @return string
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
     * @param array $field_list
     * @return array
     */
    public function getHM($key, $field_list)
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
     * @param integer $ttl expire time
     * @return boolean
     */
    public function add($key, $val, $ttl = -1)
    {
        if (!$this->_memcache) {
            return false;
        }

        if ($ttl < 0) {
            $ttl = $this->_default_ttl;
        }

        return $this->_memcache->add($key, $val, 0, $ttl);
    }
    /**
     * set value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl expire time
     * @return boolean
     */
    public function set($key, $val, $ttl = -1)
    {
        if (!$this->_memcache) {
            return false;
        }

        if ($ttl < 0) {
            $ttl = $this->_default_ttl;
        }

        return $this->_memcache->set($key, $val, 0, $ttl);
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
     * @param integer $ttl  expire time
     * @return boolean
     */
    public function replace($key, $val, $ttl = -1)
    {
        if (!$this->_memcache) {
            return false;
        }

        if ($ttl < 0) {
            $ttl = $this->_default_ttl;
        }

        return $this->_memcache->replace($key, $val, 0, $ttl);
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
        if (!$this->_memcache) {
            return false;
        }

        return $this->_memcache->increment($key, $delta);
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
     * @param string $delta
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function decrement($key, $delta = 1)
    {
        if (!$this->_memcache) {
            return false;
        }

        return $this->_memcache->decrement($key, $delta);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        if (!$this->_memcache) {
            return false;
        }

        return $this->_memcache->delete($key);
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
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key)
    {
        if (!$this->_memcache) {
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
        if (!$this->_memcache) {
            return false;
        }

        return $this->_memcache->flush();
    }
}
