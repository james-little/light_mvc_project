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
 * redis adapter for cache
 * @author ketsu.ko<jameslittle.private@gmail.com>
 * @package resource\cache\adapter
 * @version 1.0
 **/
namespace lightmvc\resource\cache\adapter;

use lightmvc\exception\CacheException;
use lightmvc\exception\ExceptionCode;
use lightmvc\info\InfoCollector;
use Redis;
use lightmvc\resource\cache\adapter\CacheAdapterInterface;
use lightmvc\resource\ResourcePool;

class AdapterRedis implements CacheAdapterInterface
{

    protected $_redis;
    private $config;

    /**
     * get memcache connection
     * @return Redis
     */
    public function getConnection()
    {
        if (!extension_loaded('redis')) {
            return null;
        }
        $resource_type = 'redis';
        $resource_pool = ResourcePool::getInstance();
        $resource_key  = $resource_pool->getResourceKey($this->config);
        $redis         = $resource_pool->getResource($resource_type, $resource_key);
        if ($redis) {
            return $redis;
        }
        $redis = new Redis();
        $redis = $this->initialize($redis);
        $resource_pool->registerResource($resource_type, $resource_key, $redis);
        return $redis;
    }
    /**
     * bind cache connection
     * @throws CacheException
     */
    public function bindConnection($connection)
    {
        if (!$connection instanceof Redis) {
            throw new CacheException(
                'connection type not match. needed: Redis, given: ' . get_class($connection),
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
        }
        $this->_redis = $connection;
    }
    /**
     * initialize redis
     * @param  Redis $redis
     * @return Redis
     */
    private function initialize($redis)
    {
        if ($this->checkIsUnixSocket($this->config)) {
            if ($redis->pconnect($this->config['host']) === false) {
                // unix socket
                throw new CacheException(
                    'redis connection failed: ' . $this->config['host'],
                    ExceptionCode::REDIS_CONNECTION_ERROR
                );
            }
        } else {
            if ($redis->pconnect(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout'],
                null,
                $this->config['retry_timeout']
            ) === false) {
                throw new CacheException(
                    'redis connection failed: ' . $this->config['host'] . ':' . $this->config['port'],
                    ExceptionCode::REDIS_CONNECTION_ERROR
                );
            }
        }
        // $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        $redis->select($this->config['dbname']);
        if ($this->config['prefix']) {
            $redis->setOption(Redis::OPT_PREFIX, $this->config['prefix']);
        }
        if ($this->config['password'] != '' && $redis->auth($this->config['password']) === false) {
            throw new CacheException(
                'redis auth failed: ' . $this->config['host'] . ':' . $this->config['password'],
                ExceptionCode::REDIS_AUTH_ERROR
            );
        }
        return $redis;
    }
    /**
     * check is host & port or unix socket
     * @param  array $config
     * @return bool
     */
    private function checkIsUnixSocket($config)
    {
        if (substr($config['host'], 0, 1) == '/') {
            return true;
        }
        return false;
    }
    /**
     * get ready
     * @return void
     */
    protected function getReady()
    {
        $this->_redis->select($this->config['dbname']);
        if ($this->config['password'] != '') {
            $this->_redis->auth($this->config['password']);
        }
    }
    /**
     * apply config
     * @param array $config
     * @throws CacheException
     */
    public function applyConfig($config)
    {
        if (empty($config) || empty($config['host'])) {
            throw new CacheException(
                'host empty',
                ExceptionCode::CACHE_ACCESS_OBJ_ERROR
            );
        }
        $redis_config         = [];
        $redis_config['host'] = $config['host'];
        if (!$this->checkIsUnixSocket($config)) {
            $redis_config['port'] = empty($config['port']) ? 6379 : $config['port'];
        }
        $redis_config['dbname']             = empty($config['dbname']) ? 0 : $config['dbname'];
        $redis_config['prefix']             = empty($config['prefix']) ? '' : $config['prefix'];
        $redis_config['password']           = empty($config['password']) ? '' : $config['password'];
        $redis_config['default_ttl']        = empty($config['default_ttl']) ? 0 : $config['default_ttl'];
        $redis_config['timeout']            = empty($config['timeout']) ? 3 : $config['timeout'];
        $redis_config['connection_timeout'] = empty($config['connection_timeout']) ?
        3000 : $config['connection_timeout'] * 1000;
        $redis_config['retry_timeout'] = empty($config['retry_timeout']) ? 3 : $config['retry_timeout'];
        $this->config                  = $redis_config;
    }
    /**
     * get cache value by key
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        $result = $this->_redis->get($array['key']);
        return $result === false ? null : $result;
    }
    /**
     * get cache value by key list
     * @param array $key_list
     * @return array
     */
    public function getM($key_list)
    {
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        $has_db_set    = false;
        $real_key_list = [];
        foreach ($key_list as $key) {
            $array = $this->parseKey($key);
            if (!$has_db_set && isset($array['db'])) {
                $this->_redis->select($array['db']);
            }
            $real_key_list[] = $array['key'];
        }
        $result_list = $this->_redis->mGet($real_key_list);
        if ($result_list === false) {
            return null;
        }
        $result_data_list = [];
        foreach ($real_key_list as $index => $real_key) {
            if (!isset($result_list[$index]) || $result_list[$index] === false) {
                $result_data_list[$real_key] = null;
                continue;
            }
            $result_data_list[$real_key] = $result_list[$index];
        }
        return $result_data_list;
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param string $key1
     * @return string
     */
    public function getH($key, $key1)
    {
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        $result = $this->_redis->hGet($array['key'], $key1);
        return $result === false ? null : $result;
    }
    /**
     * set data array as hash by key
     * @param string $key
     * @param array $key1_array
     * @return array
     */
    public function getHM($key, $key1_array)
    {
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->_redis->hMGet($array['key'], $key1_array);
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
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->set($array['key'], $val, $expire_time);
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
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
        }
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        $result = $this->_redis->setex($array['key'], $expire_time, convert_string($val));
        if ($result === false) {
            __add_info(
                'key write to server failed: ' . $key,
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
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
        }
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        $result = $this->_redis->hSet($array['key'], $key1, convert_string($val));
        if ($result === false) {
            __add_info(
                'key write to server failed: ' . $key,
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_ERR
            );
        }
        $this->_redis->setTimeout($array['key'], $expire_time);
        return $result;
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
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
        }
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        $result = $this->_redis->hMSet($array['key'], $data_array);
        if ($result === false) {
            __add_info(
                'key write to server failed: ' . $key,
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_ERR
            );
        }
        $this->touch($array['key'], $expire_time);
        return $result;
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
        return $this->set($key, $val, $expire_time);
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
        if (!$this->_redis) {
            return false;
        }

        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->_redis->incrBy($array['key'], $step);
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
        if (!$this->_redis) {
            return false;
        }

        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->_redis->hIncrBy($array['key'], $key1, $step);
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
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->_redis->decrBy($array['key'], $step);
    }
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        if (!$this->_redis) {
            return false;
        }
        __add_info(
            sprintf('cache delete. %s', $key),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->_redis->delete($array['key']);
    }
    /**
     * delete value from cache
     * @param string $key
     * @param string $key1
     * @return boolean
     */
    public function deleteH($key, $key1)
    {
        if (!$this->_redis) {
            return false;
        }
        __add_info(
            sprintf('cache delete. %s', $key),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->_redis->hDel($array['key'], $key1);
    }
    /**
     * set new expire time to key
     * @param string $key
     * @param int $expire_time
     * @return boolean
     */
    public function touch($key, $expire_time = -1)
    {
        if (!$this->_redis) {
            return false;
        }
        $this->getReady();
        if ($expire_time < 0) {
            $expire_time = $this->config['default_ttl'];
        }
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->_redis->setTimeout($array['key'], $expire_time);
    }
    /**
     * check if the key exists in the cache
     * @param string $key
     * @return boolean
     */
    public function isKeyExist($key)
    {
        if (!$this->_redis) {
            return false;
        }

        $this->getReady();
        $array = $this->parseKey($key);
        if (isset($array['db'])) {
            $this->_redis->select($array['db']);
        }
        return $this->get($array['key']) !== null;
    }
    /**
     * flush cache server
     * @return boolean
     */
    public function flush()
    {
        if (!$this->_redis) {
            return false;
        }

        $this->getReady();
        return $this->_redis->flushDB();
    }
    /**
     * parse key
     * @param string $key
     * @return array
     */
    private function parseKey($key)
    {
        $pos = strpos($key, '|');
        if ($pos === false) {
            return ['key' => $key];
        }
        $dbname   = substr($key, 0, $pos);
        $real_key = substr($key, $pos + 1);
        return ['db' => $dbname, 'key' => $real_key];
    }
}
