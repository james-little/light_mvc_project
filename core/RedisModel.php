<?php
namespace core;

use \Application,
    \Redis,
    resource\ResourcePool,
    info\InfoCollector,
    exception\ExceptionCode,
    exception\RedisException;

/**
 * model (Redis)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
class RedisModel {

    protected $redis;
    protected $table;

    /**
     * __construct
     */
    public function __construct() {
        $redis_config = $this->_getConfig();
        $host_name = $redis_config['tables'][$this->table];
        if (empty($redis_config['hosts'][$host_name])) {
            throw new RedisException(sprintf('host matched with table %s not exists: %s', $this->table, $host_name),
                ExceptionCode::REDIS_CONFIG_ERROR);
        }
        $redis_config = $redis_config['hosts'][$host_name];
        $this->_initiallize($redis_config);
    }
    /**
     * get redis config
     * @throws RedisException
     */
    protected function _getConfig() {
        $redis_config = Application::getConfigByKey('redis');
        if (!empty($redis_config)) {
            return $redis_config;
        }
        $redis_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'redis.php';
        if (!is_file($redis_config)) {
            throw new RedisException(
                'config file not exist: ' . $redis_config,
                ExceptionCode::REDIS_CONFIG_NOT_EXIST);
        }
        $redis_config = include($redis_config);
        Application::setConfig('redis', $redis_config);
        return $redis_config;
    }
    /**
     * initiallize
     * @param array $redis_config
     * @return bool
     */
    protected function _initiallize($redis_config) {

        $resource_pool = ResourcePool::getInstance();
        $resource_key = $resource_pool->getResourceKey('redis', $redis_config);
        $redis = $resource_pool->getResource('redis', $resource_key);
        if ($redis instanceof Redis) {
            $this->redis = $redis;
            return true;
        }
        $this->redis = new Redis();
        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        if ($this->redis->pconnect($redis_config['host'], $redis_config['port'], $redis_config['timeout']) === false) {
            throw new RedisException(
                    'redis connection failed: ' . $redis_config['host'] . ':' . $redis_config['port'],
                    ExceptionCode::REDIS_CONNECTION_ERROR);
        }
        if (!empty($redis_config['password']) && $this->redis->auth($redis_config['password']) === false) {
            __add_info(
                'RedisDriver#auth error',
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            throw new RedisException(
                'redis auth failed: ' . $redis_config['host'] . ':' . $redis_config['password'],
                ExceptionCode::REDIS_AUTH_ERROR);
        }
        if (!$this->redis->select($redis_config['dbname'])) {
            __add_info(
                'RedisDriver#select database failed: ' . $redis_config['dbname'],
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        if (!empty($redis_config['prefix'])) {
            $this->redis->setOption(Redis::OPT_PREFIX, $redis_config['prefix']);
        }
        $resource_pool->registerResource('redis', $resource_key, $this->redis);
        return true;
    }

}