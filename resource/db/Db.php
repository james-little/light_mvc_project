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
 * DB class(singleton)
 * =======================================================
 * This class is a abstract for database
 *
 * Example:
 *
 *     $db = Db::getInstance($db_config);
 *     $sql = 'UPDATE table SET user_id = 401 WHERE id = :id';
 *     $result = $db->getCommand()->exec($sql, array(':id' => 17));
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package resource\db
 * @version 1.0
 **/
namespace lightmvc\resource\db;

use lightmvc\ClassLoader;
use lightmvc\exception\DbException;
use lightmvc\exception\ExceptionCode;
use lightmvc\resource\db\command\DbCommand;

class Db
{

    // config
    private $config;
    private $table_config_mapping;
    protected static $_instance;

    /**
     *
     * @param array $config
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
     * __construct
     * @param array $config
     */
    protected function __construct()
    {
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
    public function applyConfig($table_name, $config)
    {
        if (empty($config) || empty($table_name)) {
            return;
        }
        $config_key                              = $this->getConfigKey($config);
        $this->config[$config_key]               = $config;
        $this->table_config_mapping[$table_name] = $config_key;
        return $this;
    }
    /**
     * get config key
     * @param array $config
     */
    private function getConfigKey($config)
    {
        return md5(_serialize($config));
    }
    /**
     * get driver
     * @param string $adapter
     * @param array $config
     * @throws DbException
     */
    public function getDriver($driver_name)
    {
        $driver = null;
        switch ($driver_name) {
            case 'mysql':
                $driver = ClassLoader::loadClass('\lightmvc\resource\db\driver\DbDriverMysql');
                break;
            case 'mysqli':
                $driver = ClassLoader::loadClass('\lightmvc\resource\db\driver\DbDriverMysqli');
                break;
            case 'postgresql':
                // $driver = new DbDriverPostgreSQL($config);
                // break;
        }
        if (!$driver) {
            throw new DbException(
                'driver is not supported yet: ' . $driver_name,
                ExceptionCode::DB_NOT_SUPPORT
            );
        }
        return $driver;
    }
    /**
     * get command instance
     * @param string $table_name
     * @param boolean $force_master
     * @return DbCommand
     */
    public function getCommand($table_name, $force_master = false)
    {
        if (empty($this->table_config_mapping[$table_name])) {
            throw new DbException(
                "table name not exist: {$table_name}",
                ExceptionCode::DB_CONFIG_NOT_EXIST
            );
        }
        $config_key = $this->table_config_mapping[$table_name];
        if (empty($this->config[$config_key])) {
            throw new DbException(
                "config empty: {$table_name}. key: {$config_key}",
                ExceptionCode::DB_CONFIG_NOT_EXIST
            );
        }
        $command_config                 = $this->config[$config_key];
        $command_config['force_master'] = $force_master;
        $driver                         = $this->getDriver($command_config['driver']);
        $this->checkDbConfig($command_config);
        $db_command = DbCommand::getInstance();
        $db_command->applyDriver($driver, $command_config);
        if (!empty($command_config['privilege'])) {
            $db_command->applyPrivilege($command_config['privilege']);
        }
        return $db_command;
    }
    /**
     * check db config
     * @throws DbException
     */
    private function checkDbConfig($config)
    {
        if (empty($config['servers']['m'])) {
            throw new DbException('master config empty', ExceptionCode::DB_CONFIG_NOT_EXIST);
        }
    }
}
