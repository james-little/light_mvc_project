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
 * mysqli driver class
 * =======================================================
 * mysqli implementation of ad reward db driver
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package resource\db\driver
 * @version 1.0
 **/
namespace lightmvc\resource\db\driver;

use lightmvc\Exception;
use lightmvc\exception\DbException;
use lightmvc\exception\ExceptionCode;
use lightmvc\info\InfoCollector;
use MySQLi;
use lightmvc\resource\db\driver\DbDriverInterface;
use lightmvc\resource\ResourcePool;

class DbDriverMysqli implements DbDriverInterface
{

    const METHOD_QUERY_ALL    = 1;
    const METHOD_QUERY_ROW    = 2;
    const METHOD_QUERY_COLUMN = 3;
    const METHOD_EXEC         = 4;
    const RESOURCE_TYPE       = 'mysqli';

    private $mysqli;
    private $statement;
    private $is_asyn = false;

    /*
     * set mysqli to null when destruct
     */
    public function __destruct()
    {
        $this->mysqli = null;
    }
    /**
     * __clone
     */
    public function __clone()
    {
        $this->mysqli = null;
    }

    /**
     * Executes the SQL statement and returns all rows.
     * when query_cache is needed,  then $isUseCache must be set to true
     */
    public function queryAll($sql, $param = [], $fetch_associative = true)
    {
        $arg                      = [];
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query(self::METHOD_QUERY_ALL, $sql, $param, $arg);
    }
    /**
     * get the first row of the result set
     * when query_cache is needed,  then $isUseCache must be set to true
     * @return  false when failed
     */
    public function queryRow($sql, $param = [], $fetch_associative = true)
    {
        $arg                      = [];
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query(self::METHOD_QUERY_ROW, $sql, $param, $arg);
    }
    /**
     * Executes the SQL statement and returns the {0_based_index} column of the result set.
     * @return array the first column of the query result. Empty array if no result.
     */
    public function queryColumn($sql, $param = [], $column_index = 0)
    {
        $arg                 = [];
        $arg['column_index'] = $column_index;
        return $this->query(self::METHOD_QUERY_COLUMN, $sql, $param, $arg);
    }
    /**
     * exec non-query commands like insert, delete...
     * return affected rows
     */
    public function exec($sql, $param = [], $is_get_last_insert_id = false)
    {
        $arg                       = [];
        $arg['get_last_insert_id'] = (bool) $is_get_last_insert_id;
        return $this->query(self::METHOD_EXEC, $sql, $param, $arg);
    }
    /**
     * open database connection
     */
    public function bindConnection($connection)
    {
        if (!$connection instanceof MySQLi) {
            throw new DbException(
                sprintf('connection type not match. needed: MySQLi, given: %s', get_class($connection)),
                ExceptionCode::DB_ACCESS_OBJ_ERROR
            );
        }
        $this->mysqli = $connection;
    }
    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    private function close()
    {
        $this->mysqli = null;
    }
    /**
     * $param = array(
     *     %key% => %value%
     * )
     * sql: SELECT id FROM movie WHERE id > :id_min AND id < :id_max
     * eg: $param = array(
     *         ':id_max' => $idMax,
     *         ':id_min' => $idMin
     *     )
     *
     * $arg optional. fetch_associative | column_index
     * $arg = array(
     *     %element% => %value%
     * )
     * @param string method of mysqli to be called
     * @return array | int
     *     SELECT : array
     *     INSERT : last_insert_id/affected_rows. affected_rows is returned
     *              when last_insert_id is empty
     *     UPDATE : affected_rows
     * @throws DbException
     */
    private function query($method, $sql, $param = [], $arg = [])
    {
        if (!$this->mysqli instanceof MySQLi) {
            throw new DbException('mysqli not set', ExceptionCode::DB_ACCESS_OBJ_ERROR);
        }
        $result = null;
        if (empty($param)) {
            $param = [];
        }
        try {
            $this->statement = $this->mysqli->prepare($sql);
            $is_assoc = false;
            if (!empty($arg['fetch_associative'])) {
                $is_assoc = true;
            }
            if ($param !== []) {
                $stmt_params = [];
                $stmt_params[0] = '';
                foreach ($param as $key => $val) {
                    if (is_string($val)) {
                        $stmt_params[0] .= 's';
                    } elseif (is_int($val)) {
                        $stmt_params[0] .= 'i';
                    } elseif (is_float($val)) {
                        $stmt_params[0] .= 'f';
                    } else {
                        $stmt_params[0] .= 'b';
                    }
                    $stmt_params[] = &$param[$key];
                }
                call_user_func_array([$this->statement, 'bind_param'], $stmt_params);
            }
            $is_success = $this->statement->execute();
            if (!$is_success) {
                $last_error_code = $this->statement->errorno;
                $last_error_info = $this->statement->error;
                throw new DbException(
                    sprintf('mysqli execute error[ %s ]: %s; sql:%s', $last_error_code, $last_error_info, $sql),
                    ExceptionCode::DB_EXECUTE_ERROR
                );
            }
            switch ($method) {
                case self::METHOD_QUERY_COLUMN:
                case self::METHOD_QUERY_ALL:
                case self::METHOD_QUERY_ROW:
                    $stat_result = $this->statement->get_result();
                    if ($method == self::METHOD_QUERY_COLUMN) {
                        $column_index = isset($arg['column_index']) ? $arg['column_index'] : 0;
                        $result       = $stat_result->fetch_field_direct($column_index);
                    } elseif ($method == self::METHOD_QUERY_ALL) {
                        $result = $stat_result->fetch_all($is_assoc ? MYSQLI_ASSOC : MYSQLI_NUM);
                    } elseif ($method == self::METHOD_QUERY_ROW) {
                        $result = $stat_result->fetch_array($is_assoc ? MYSQLI_ASSOC : MYSQLI_NUM);
                    }
                    $stat_result->free();
                    break;
                case self::METHOD_EXEC:
                    $sql_type_str = strtolower(substr($sql, 0, 6));
                    if ($sql_type_str == 'insert' || $sql_type_str == 'update') {
                        // return last_insert_id when get_last_insert_id flag is true
                        // but if last_insert_id is empty, return affected_rows
                        if (empty($arg['get_last_insert_id'])) {
                            $result = $this->statement->affected_rows;
                        } else {
                            $result = $this->mysqli->insert_id;
                            // return affected_rows if last_insert_id is empty
                            if (empty($result)) {
                                $result = $this->statement->affected_rows;
                            }
                        }
                    } else {
                        $result = 0;
                    }
                    break;
            }
            $this->statement->close();
        } catch (Exception $e) {
            throw new DbException(
                sprintf('db error: %s, sql: %s', $e->getMessage(), $sql),
                ExceptionCode::DB_FETCH_ERROR
            );
        }
        $this->close();
        return $result;
    }
    /**
     * begin transaction
     * @return  bool: true when successful
     */
    public function beginTransaction()
    {
        return $this->mysqli->begin_transaction();
    }
    /**
     * commit
     * @return  bool: true when successful
     */
    public function commit()
    {
        return $this->mysqli->commit();
    }
    /**
     * rollback
     * @return  bool: true when successful
     */
    public function rollback()
    {
        return $this->mysqli->rollBack();
    }
    /**
     * get connection
     * @throws DbException
     */
    public function getConnection($config)
    {
        if (!extension_loaded('mysqli')) {
            return null;
        }
        $mysql_config  = $this->filterMysqlConfig($config);
        $resource_pool = ResourcePool::getInstance();
        $resource_key  = $resource_pool->getResourceKey($mysql_config);
        $mysqli        = $resource_pool->getResource(self::RESOURCE_TYPE, $resource_key);
        if ($mysqli) {
            return $mysqli;
        }
        $mysqli = mysqli_init();
        if (!$mysqli) {
            throw new DbException('mysqli initialize error', ExceptionCode::DB_ACCESS_OBJ_ERROR);
        }
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $mysql_config['conn_timeout']);
        $is_success = false;
        if (empty($mysql_config['socket'])) {
            $is_success = $mysqli->real_connect(
                $mysql_config['host'],
                $mysql_config['dbuser'],
                $mysql_config['dbpass'],
                $mysql_config['dbname'],
                $mysql_config['port'],
                null,
                MYSQLI_CLIENT_COMPRESS
            );
        } else {
            $is_success = $mysqli->real_connect(
                null,
                $mysql_config['dbuser'],
                $mysql_config['dbpass'],
                $mysql_config['dbname'],
                $mysql_config['port'],
                $mysql_config['socket'],
                MYSQLI_CLIENT_COMPRESS
            );
        }
        if (!$is_success) {
            __add_info(
                'DbDriverMysqli#mysqli connect error: ' . $mysqli->connect_error,
                InfoCollector::TYPE_EXCEPTION,
                InfoCollector::LEVEL_DEBUG
            );
            throw new DbException('db error: ' . $e->getMessage(), ExceptionCode::DB_ACCESS_OBJ_ERROR);
        }
        $resource_pool->registerResource(self::RESOURCE_TYPE, $resource_key, $mysqli);
        return $mysqli;
    }
    /**
     * filter mysql config
     * @param array $config
     * @throws DbException
     */
    private function filterMysqlConfig($config)
    {
        if (empty($config) || (empty($config['host']) && empty($config['socket'])) || empty($config['dbname'])) {
            throw new DbException('not enough configuration information', ExceptionCode::DB_CONFIG_NOT_EXIST);
        }
        $mysql_config           = [];
        $mysql_config['host']   = empty($config['host']) ? 'localhost' : $config['host'];
        $mysql_config['port']   = empty($config['port']) ? 3306 : $config['port'];
        $mysql_config['socket'] = empty($config['socket']) ? null : $config['socket'];
        $mysql_config['dbname'] = empty($config['dbname']) ? '' : $config['dbname'];
        $mysql_config['dbuser'] = empty($config['dbuser']) ? '' : $config['dbuser'];
        $mysql_config['dbpass'] = empty($config['dbpass']) ? '' : $config['dbpass'];
        $mysql_config['conn_timeout'] = empty($config['conn_timeout']) ? 500 : $config['conn_timeout'] * 1000;
        if (!empty($config['use_select_asyn'])) {
            $this->is_asyn = true;
        }
        return $mysql_config;
    }
}
