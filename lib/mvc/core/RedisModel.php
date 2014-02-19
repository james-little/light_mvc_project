<?php
namespace core;

use \Redis,
    resource\ResourcePool,
    info\InfoCollector,
    exception\ExceptionCode,
    exception\NosqlException;

/**
 * model (Redis)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
class RedisModel {

    protected $_redis;
    protected $_table;

    /**
     * __construct
     */
    public function __construct() {
        $redis_config = $this->_getConfig();
        $db_name = $redis_config['tables'][$this->_table];
        if (empty($redis_config['hosts'][$db_name])) {
            throw new NosqlException(__message('host matched with table %s not exists: %s', array($this->_table, $db_name)),
                ExceptionCode::NOSQL_CONFIG_ERROR);
        }
        $redis_config = $redis_config['hosts'][$db_name];
        if (!$this->_initiallize($redis_config)) {
            throw new NosqlException(__message('redis server connection error'), ExceptionCode::NOSQL_CONFIG_ERROR);
        }
    }
    /**
     * get db config
     * @throws AppException
     */
    protected function _getConfig() {
        $redis_config = \AppRuntimeContext::getInstance()->getData('redis_config');
        if (empty($redis_config)) {
            $redis_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DIRECTORY_SEPARATOR . 'redis.php';
            if (!file_exists($db_config)) {
                throw new NosqlException(
                    __message('config file not exist: %s',array($db_config)),
                    ExceptionCode::BUSINESS_DB_CONFIG_NOT_EXIST);
            }
            $redis_config = include($redis_config);
            \AppRuntimeContext::getInstance()->setData('redis_config', $redis_config);
        }
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
            $this->_redis = $redis;
            return true;
        }
        $this->_redis = new Redis();
        if ($this->_redis->pconnect($redis_config['host'], $redis_config['port'], $redis_config['timeout']) === false) {
            __add_info(
                __message('DbDriverRedis#connection error'),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        if (!$this->_redis->auth($redis_config['password'])) {
            __add_info(
                __message('DbDriverRedis#auth error'),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        if (!$this->_redis->select($redis_config['dbname'])) {
            __add_info(
                __message('DbDriverRedis#select database failed: %s', array($redis_config['dbname'])),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        if (!empty($redis_config['prefix'])) {
            $this->_redis->setOption(Redis::OPT_PREFIX, $redis_config['prefix']);
        }
        $resource_pool->registerResource('redis', $resource_key, $this->_redis);
        return true;
    }

}