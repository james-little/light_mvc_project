<?php

namespace resource\db;

use exception\DbException,
    resource\ResourcePool,
    resource\db\driver\DbDriverMysql,
    resource\db\command\DbCommand;

/**
 * DB class(singleton)
 * =======================================================
 * This class is a abstract for database
 *
 * Example:
 *
 *     $db = Db::getInstance($db_config);
 *     $sql = 'UPDATE ad_reward_user_tracking SET user_id = 401 WHERE id = :id';
 *     $result = $db->getCommand()->exec($sql, array(':id' => 17));
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package resource\db
 * @version 1.0
 **/


class Db {

    // config
    private $config;
    protected static $_instance;

    /**
     *
     * @param array $config
     */
    static public function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * __construct
     * @param array $config
     */
    protected function __construct() {}
    /**
     * __destruct
     */
    public function __destruct() {
        if ($this->config) $this->config = null;
    }
    /**
     * initialize the db class.
     * config:
     *     driver: driver supported (now only mysql)
     *     host: database hosts
     *     port: database port(default: 3306)
     *     dbname: database name
     *     dbuser: database user name
     *     dbpass: database user password
     */
    public function applyConfig($config) {
        if (empty($config)) {
            return ;
        }
        $this->config = $config;
        return $this;
    }
    /**
     * get driver
     * @param string $adapter
     * @param array $config
     * @throws DbException
     */
    public function getDriver($driver_name) {
        $driver = null;
        switch ($driver_name) {
            case 'mysql':
                $driver = \ClassLoader::loadClass('\resource\db\driver\DbDriverMysql');
                break;
            case 'odbc':
//              $driver = new DbDriverMysql($config);
//              break;
        }
        if (!$driver) {
            throw new DbException(__message('driver is not supported yet: %s', array($driver_name)));
        }
        return $driver;
    }
    /**
     * get command instance
     * @return DbCommand
     */
    public function getCommand() {
        $this->checkDbConfig();
        $driver = $this->getDriver($this->config['driver']);
        return DbCommand::getInstance()->applyDriver($driver, $this->config);
    }
    /**
     * check db config
     * @throws DbException
     */
    private function checkDbConfig() {
        if (empty($this->config)) {
            throw new DbException(__message('db config is empty'));
        }
        if (empty($this->config['servers']['m'])) {
            throw new DbException(__message('master config empty'));
        }
    }
}