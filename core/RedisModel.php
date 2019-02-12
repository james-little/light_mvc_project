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
 * model (Redis)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
namespace lightmvc\core;

use lightmvc\Application;
use lightmvc\exception\ExceptionCode;
use lightmvc\exception\RedisException;
use Redis;
use lightmvc\resource\ResourcePool;

class RedisModel
{

    protected $redis;
    protected $table;
    private $dbname;
    private $auth;

    /**
     * __construct
     */
    public function __construct()
    {
        if (!extension_loaded('redis')) {
            throw new RedisException('redis extendtion not loaded', ExceptionCode::REDIS_DEFAULT_ERROR);
        }
        $redis_config = $this->_getConfig();
        $host_name    = $redis_config['tables'][$this->table];
        if (empty($redis_config['hosts'][$host_name])) {
            throw new RedisException(
                sprintf('host matched with table %s not exists: %s', $this->table, $host_name),
                ExceptionCode::REDIS_CONFIG_ERROR
            );
        }
        $redis_config = $redis_config['hosts'][$host_name];
        $redis_config = $this->filterRedisConfig($redis_config);
        $this->redis  = $this->getRedis($redis_config);
    }
    /**
     * destruct
     */
    public function __destruct()
    {
        $this->redis->close();
    }
    /**
     * apply config
     * @param array $config
     * @throws RedisException
     */
    private function filterRedisConfig($config)
    {
        if (empty($config) || empty($config['host'])) {
            throw new RedisException(
                'host empty',
                ExceptionCode::REDIS_CONFIG_ERROR
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
        $redis_config['connection_timeout'] = empty($config['connection_timeout']) ? 3000 : $config['connection_timeout'] * 1000;
        $redis_config['retry_timeout']      = empty($config['retry_timeout']) ? 3 : $config['retry_timeout'];
        return $redis_config;
    }
    /**
     * get redis config
     * @throws RedisException
     */
    protected function _getConfig()
    {
        $redis_config = Application::getConfigByKey('redis');
        if (!empty($redis_config)) {
            return $redis_config;
        }
        $redis_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'redis.php';
        if (!is_file($redis_config)) {
            throw new RedisException(
                'config file not exist: ' . $redis_config,
                ExceptionCode::REDIS_CONFIG_NOT_EXIST
            );
        }
        $redis_config = include $redis_config;
        Application::setConfig('redis', $redis_config);
        return $redis_config;
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
     * get memcache connection
     * @return Redis
     */
    private function getRedis($redis_config)
    {
        if (!extension_loaded('redis')) {
            return null;
        }
        $resource_type = 'redis';
        $resource_pool = ResourcePool::getInstance();
        $resource_key  = $resource_pool->getResourceKey($redis_config);
        $redis         = $resource_pool->getResource($resource_type, $resource_key);
        if ($redis) {
            return $redis;
        }
        $redis = new Redis();
        $redis = $this->initialize($redis, $redis_config);
        $resource_pool->registerResource($resource_type, $resource_key, $redis);
        return $redis;
    }
    /**
     * initialize redis
     * @param  Redis $redis
     * @return Redis
     */
    private function initialize($redis, $redis_config)
    {
        if ($this->checkIsUnixSocket($redis_config)) {
            if ($redis->pconnect($redis_config['host']) === false) {
                // unix socket
                throw new RedisException(
                    'redis connection failed: ' . $redis_config['host'],
                    ExceptionCode::REDIS_CONNECTION_ERROR
                );
            }
        } else {
            if ($redis->pconnect(
                $redis_config['host'],
                $redis_config['port'],
                $redis_config['timeout'],
                null,
                $redis_config['retry_timeout']
            ) === false) {
                throw new RedisException(
                    'redis connection failed: ' . $redis_config['host'] . ':' . $redis_config['port'],
                    ExceptionCode::REDIS_CONNECTION_ERROR
                );
            }
        }
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        $redis->select($redis_config['dbname']);
        if ($redis_config['prefix']) {
            $redis->setOption(Redis::OPT_PREFIX, $redis_config['prefix']);
        }
        if ($redis_config['password'] != '' && $redis->auth($redis_config['password']) === false) {
            throw new RedisException(
                'redis auth failed: ' . $redis_config['host'] . ':' . $redis_config['password'],
                ExceptionCode::REDIS_AUTH_ERROR
            );
        }
        $this->dbname = $redis_config['dbname'];
        $this->auth   = $redis_config['password'];
        return $redis;
    }
    /**
     * get ready
     * @return void
     */
    protected function getReady()
    {
        $this->redis->select($this->dbname);
        if ($this->auth != '') {
            $this->redis->auth($this->auth);
        }
    }
}
