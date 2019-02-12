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
 * APC adapter for cache
 *@author ketsu.ko<jameslittle.private@gmail.com>
 *@package resource\cache\adapter
 *@version 1.0
 *
 */

namespace lightmvc\resource\cache\adapter;

use lightmvc\resource\cache\adapter\CacheAdapterInterface;

class AdapterApc implements CacheAdapterInterface
{

    private $_default_ttl = 0;

    /**
     * get cache connection
     * @see resource\cache\adapter.CacheAdapterInterface::getConnection()
     */
    public function getConnection()
    {
        return $this;
    }
    /**
     * (non-PHPdoc)
     * @see resource\cache\adapter.CacheAdapterInterface::bindConnection()
     */
    public function bindConnection($connection)
    {
        return $this;
    }
    /**
     * apply config
     * @param array $config
     */
    public function applyConfig($config)
    {
        if (empty($config)) {
            return;
        }

        $this->_default_ttl = empty($config['default_ttl']) ? 0 : $config['default_ttl'];
    }
    /**
     * set value by key
     * @param string $key
     * @param string $val
     * @param integer $ttl expire time
     * @return boolean
     */
    public function set($key, $val, $ttl = 0)
    {
        if ($ttl < 0) {
            $ttl = $this->_default_ttl;
        }
        return apc_store($key, $val, $ttl);
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
        return false;
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
        return false;
    }
    /**
     * get cache value by key
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $result = apc_fetch($key);
        return $result === false ? null : $result;
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param string $key1
     * @return string
     */
    public function getH($key, $key1)
    {
        return null;
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param array $key1_array
     * @return array
     */
    public function getHM($key, $key1_array)
    {
        return null;
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
    public function increment($key, $delta = 1)
    {
        return apc_inc($key, $delta);
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
        return false;
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
        return apc_dec($key, $delta);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        return apc_delete($key);
    }
    /**
     * delete value from cache
     * @param string $key
     * @param string $key1
     * @return boolean
     */
    public function deleteH($key, $key1)
    {
        return false;
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     */
    public function isKeyExist($key)
    {
        return apc_exists($key);
    }
    /**
     * flush cache server
     * @return boolean
     */
    public function flush()
    {
        return apc_clear_cache();
    }
}
