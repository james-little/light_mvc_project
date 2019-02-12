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
 * cache adapter interface
 * =======================================================
 * defines basic operations for cache system
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @package resource\cache\adapter
 * @version 1.1
 **/
namespace lightmvc\resource\cache\adapter;

interface CacheAdapterInterface
{

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
     * @param int $expire_time seconds
     * @return boolean
     */
    public function set($key, $value, $expire_time);
    /**
     * set value as hash by key
     * @param string $key
     * @param string $key1
     * @param string $val
     * @param int $expire_time expire time
     * @return boolean
     */
    public function setH($key, $key1, $val, $expire_time = -1);
    /**
     * set data array as hash by key
     * @param string $key
     * @param array $data_array
     * @param int $expire_time expire time
     * @return boolean
     */
    public function setHM($key, $data_array, $expire_time = -1);
    /**
     * get value from cache
     * @param string $key
     * @return mixed
     */
    public function get($key);
    /**
     * get value from cache(Hash)
     * @param string $key
     * @param string $key1
     * @return mixed
     */
    public function getH($key, $key1);
    /**
     * get value from cache(Hash)
     * @param string $key
     * @param array $key1_array
     * @return array
     */
    public function getHM($key, $key1_array);
    /**
     * delete value from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key);
    /**
     * delete value from cache
     * @param string $key
     * @param string $key1
     * @return boolean
     */
    public function deleteH($key, $key1);
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
     * increase some value to specified value by key(Hash)
     * @param string $key
     * @param string $key1
     * @param int $step
     * @return int | false:
     *         new value when success,
     *         false when failed
     */
    public function incrementH($key, $key1, $step = 1);
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
