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
 * model (DB)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
namespace lightmvc\core;

use lightmvc\Application;
use lightmvc\exception\DbException;
use lightmvc\exception\ExceptionCode;
use lightmvc\exception\ModelException;
use lightmvc\info\InfoCollector;
use lightmvc\resource\db\Db;

class Model
{

    private $db;
    protected $table;
    protected $pk = 'id';
    protected $default_column_list;
    protected $force_master = false;
    protected $force_index;
    protected $custom_table_param;

    /**
     * __construct
     */
    public function __construct()
    {
        $db_config = $this->_getConfig();
        if (empty($db_config['tables'][$this->table])) {
            throw new DbException(
                sprintf('config not defined in database tables section: %s', $this->table),
                ExceptionCode::DB_CONFIG_NOT_EXIST
            );
        }
        $db_name = $db_config['tables'][$this->table];
        if (empty($db_config['hosts'][$db_name])) {
            throw new DbException(
                sprintf('db host not defined in hosts section: %s', $db_name),
                ExceptionCode::DB_CONFIG_NOT_EXIST
            );
        }
        $db_config = $db_config['hosts'][$db_name];
        if (!empty($db_config['privilege'])) {
            $db_config['privilege'] = $db_config['privilege'];
        }
        $this->db = Db::getInstance()->applyConfig($this->table, $db_config);
    }
    /**
     * get solr config
     * @throws ModelException
     */
    protected function _getConfig()
    {
        $db_config = Application::getConfigByKey('database');
        if (empty($db_config)) {
            $db_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'database.php';
            if (!is_file($db_config)) {
                throw new ModelException(
                    sprintf('db config file not exist: %s', $db_config),
                    ExceptionCode::DB_CONFIG_NOT_EXIST
                );
            }
            $db_config = include $db_config;
            $db_config = $this->overWriteDbConfigForDebugDevice($db_config);
            $db_config = $this->overWriteDbConfigForPHPUnit($db_config);
            Application::setConfig('database', $db_config);
        }
        return $db_config;
    }
    /**
     * over write db config for debug device
     * chage database to %dbname%_debug
     */
    private function overWriteDbConfigForDebugDevice($db_config)
    {
        if (defined('APPLICATION_IS_SANDBOX') && APPLICATION_IS_SANDBOX) {
            foreach ($db_config['hosts'] as $host_alias => $host_config) {
                // master
                $db_config['hosts'][$host_alias]['servers']['m']['dbname'] .= '_sandbox';
                if (!$db_config['hosts'][$host_alias]['replication']) {
                    continue;
                }
                // slave
                foreach ($host_config['servers']['s'] as $key => $slave_config) {
                    $db_config['hosts'][$host_alias]['servers']['s'][$key]['dbname'] .= '_sandbox';
                }
            }
        }
        return $db_config;
    }
    /**
     * over write db config for phpunit
     * chage database to %dbname%_debug
     */
    private function overWriteDbConfigForPHPUnit($db_config)
    {
        if (defined('IS_PHPUNIT') && IS_PHPUNIT) {
            foreach ($db_config['hosts'] as $host_alias => $host_config) {
                // master
                $db_config['hosts'][$host_alias]['servers']['m']['dbname'] .= '_test';
                if (!$db_config['hosts'][$host_alias]['replication']) {
                    continue;
                }
                // slave
                foreach ($host_config['servers']['s'] as $key => $slave_config) {
                    $db_config['hosts'][$host_alias]['servers']['s'][$key]['dbname'] .= '_test';
                }
            }
        }
        return $db_config;
    }
    /**
     * query by 'AND', the result would be only the first row
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function queryByAnd($and_list, $not_list = null, $column_list = null, $order = null, $offset = null)
    {
        $sql_params = $this->createSqlParamsByAnd($and_list, $not_list, $column_list, $order, 1, $offset);
        if (!$sql_params) {
            return [];
        }
        return $this->queryRow($sql_params['sql'], $sql_params['params']);
    }
    /**
     * query by 'AND'. return all the rows match the condition
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function queryAllByAnd(array $and_list, array $not_list = null, array $column_list = null, $order = null, $limit = null, $offset = null)
    {
        $sql_params = $this->createSqlParamsByAnd($and_list, $not_list, $column_list, $order, $limit, $offset);
        if (!$sql_params) {
            return false;
        }
        return $this->queryAll($sql_params['sql'], $sql_params['params']);
    }
    /**
     * query count by and
     * @param array $and_list
     * @param array $not_list
     * @param string $count_column_name
     * @return int
     */
    public function queryCountByAnd(array $and_list, array $not_list = null, $count_column_name = null)
    {
        $is_and_list_not_null     = is_array($and_list) && count($and_list) ? true : false;
        $is_not_and_list_not_null = is_array($not_list) && count($not_list) ? true : false;
        $query                    = '1 ';
        $params                   = [];
        if ($is_and_list_not_null) {
            foreach ($and_list as $column => $value) {
                $query .= " AND {$column} = :{$column}";
                $params[":{$column}"] = $value;
            }
        }
        if ($is_not_and_list_not_null) {
            foreach ($not_list as $column => $value) {
                $query .= " AND {$column} != :{$column}";
                $params[":{$column}"] = $value;
            }
        }
        return $this->queryCount($count_column_name, $query, $params);
    }
    /**
     * get count of the rows you want to query
     * @example
     *     $query = ' user_id > :user_id AND id >= :id';
     *     $param = array(':user_id' => 1, ':id' => 1000);
     *     $this->queryCount('id', $query, $param);
     * @param string $column
     * @param string $query
     * @param array $param
     * @return int: failed when < 0
     */
    public function queryCount($column, $query, $params = [])
    {
        if (!$column || !is_string($column)) {
            $column = is_array($this->pk) ? '*' : $this->pk;
        }
        $table       = $this->getTableName($this->custom_table_param);
        $force_index = '';
        if ($this->force_index) {
            $force_index = " FORCE INDEX(`{$this->force_index}`) ";
        }
        $sql = "SELECT COUNT({$column}) AS ct FROM `{$table}` {$force_index} ";
        if (!empty($query)) {
            $sql .= " WHERE {$query} ";
        }
        $result = $this->queryRow($sql, $params);
        return empty($result) ? 0 : $result['ct'];
    }
    /**
     * Executes the SQL statement and returns all rows.
     * @param string $where
     * @param array  $param
     * @param array  $column_list
     * @param string  $order
     * @param int  $limit
     * @param int  $offset
     * @return int | array :failed when < 0
     */
    public function queryAllByWhere($where, $params = [], $column_list = [], $order = null, $limit = null, $offset = null)
    {
        $table       = $this->getTableName($this->custom_table_param);
        $force_index = '';
        if ($this->force_index) {
            $force_index = " FORCE INDEX(`{$this->force_index}`) ";
        }
        $sql = 'SELECT ';
        if (empty($column_list)) {
            $sql .= '*';
        } else {
            $sql .= '`' . implode('`,`', $column_list) . '`';
        }
        $sql .= " FROM `{$table}` {$force_index}";
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        if ($order) {
            $sql .= " ORDER BY {$order}";
        }

        if ($limit) {
            if ($offset == null) {
                $offset = 0;
            }

            $sql .= " LIMIT {$offset}, {$limit}";
        }
        return $this->queryAll($sql, $params);
    }
    /**
     * Executes the SQL statement and returns the first row
     * @param string $where
     * @param array $param
     * @param array $column_list
     * @param string $order
     * @param int $offset
     * @return int | array: failed when < 0
     */
    public function queryByWhere($where, array $params = [], array $column_list = [], $order = null, $offset = null)
    {
        $table       = $this->getTableName($this->custom_table_param);
        $force_index = '';
        if ($this->force_index) {
            $force_index = " FORCE INDEX(`{$this->force_index}`) ";
        }
        $sql = 'SELECT ';
        if (empty($column_list)) {
            $sql .= '*';
        } else {
            $sql .= '`' . implode('`,`', $column_list) . '`';
        }
        $sql .= " FROM `{$table}` {$force_index}";
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        if ($order) {
            $sql .= " ORDER BY {$order} ";
        }
        if ($offset == null) {
            $offset = 0;
        }
        $sql .= " LIMIT {$offset}, 1";
        return $this->queryRow($sql, $params);
    }
    /**
     * increment
     * @param string $column_name
     * @param array $where_condition
     * @param int $step
     * @return int: affected_rows or failed
     */
    public function increment($column_name, $where_condition, $step = 1)
    {
        if (empty($column_name)) {
            return 0;
        }

        $table                 = $this->getTableName($this->custom_table_param);
        $sql                   = "UPDATE `{$table}` SET `{$column_name}` = `{$column_name}` + {$step} ";
        $where_condition_array = $this->createWhereConditionByArray($where_condition);
        $param_list            = [];
        if (!empty($where_condition_array['sql'])) {
            $sql .= ' WHERE ' . $where_condition_array['sql'];
            $param_list = $where_condition_array['params'];
        }
        $affected_rows = $this->exec($sql, $param_list);
        return $affected_rows < 0 ? 0 : $affected_rows;
    }
    /**
     * decrement
     * @param string $column_name
     * @param array $where_condition
     * @param int $step
     * @return int
     */
    public function decrement($column_name, $where_condition, $step = 1)
    {
        if (empty($column_name)) {
            return 0;
        }
        $table                 = $this->getTableName($this->custom_table_param);
        $sql                   = "UPDATE `{$table}` SET `{$column_name}` = `{$column_name}` - {$step} ";
        $where_condition_array = $this->createWhereConditionByArray($where_condition);
        $param_list            = [];
        if (!empty($where_condition_array['sql'])) {
            $sql .= ' WHERE ' . $where_condition_array['sql'];
            $param_list = $where_condition_array['params'];
        }
        $affected_rows = $this->exec($sql, $param_list);
        return $affected_rows < 0 ? 0 : $affected_rows;
    }
    /**
     * set the isForce flag true if you want to insert a record with nothing
     * @param array $data_map
     * @param boolean $is_force
     * @param boolean $is_ignore
     * @return int: last_insert_id/affected_rows.
     *              affected_rows is returned when last_insert_id is empty
     */
    public function insert($data_map, $is_force = false, $is_ignore = false)
    {
        $data_map = $this->filterInputDataMap($data_map);
        if (empty($data_map)) {
            return 0;
        }
        $table       = $this->getTableName($this->custom_table_param);
        $column_list = array_keys($data_map);
        if (is_string($this->pk) && !$is_force) {
            unset($data_map[$this->pk]);
        }
        $columns_str = '`' . implode('`, `', $column_list) . '`';
        $values_str  = ':' . implode(', :', $column_list);
        $ignore      = $is_ignore ? 'IGNORE' : '';
        $sql         = "INSERT {$ignore} INTO `{$table}` ({$columns_str}) VALUES ($values_str)";
        $param_list  = [];
        foreach ($data_map as $column => $value) {
            $param_list[":{$column}"] = $value;
        }
        $result = $this->exec($sql, $param_list, true);
        return $result < 0 ? 0 : $result;
    }
    /**
     * update
     * @param array $data_map
     * @param array $where_condition_list
     *     example:
     *         array(
     *             'id >=' => 1,
     *             'id <=' => 100
     *         )
     * @param boolean $with_pk
     * @return int: affected_rows
     */
    public function update($data_map, $where_condition_list = [], $with_pk = false)
    {
        $data_map = $this->filterInputDataMap($data_map);
        if (empty($data_map)) {
            return 0;
        }
        if (empty($where_condition_list)) {
            __add_info(
                sprintf('where condition is empty, blocked for safety: %s', get_class($this)),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_ALERT
            );
            return 0;
        }
        if ($with_pk === false) {
            if (is_array($this->pk)) {
                foreach ($this->pk as $val) {
                    unset($data_map[$val]);
                }
            } else {
                unset($data_map[$this->pk]);
            }
        }
        $table       = $this->getTableName($this->custom_table_param);
        $column_list = array_keys($data_map);
        $param_list  = [];
        $sql         = " UPDATE `{$table}` SET ";
        foreach ($column_list as $column) {
            $sql .= "`{$column}` = :{$column},";
            $param_list[":{$column}"] = $data_map[$column];
        }
        $sql                   = substr($sql, 0, -1);
        $where_condition_array = $this->createWhereConditionByArray($where_condition_list);
        if (!empty($where_condition_array['sql'])) {
            $sql .= ' WHERE ' . $where_condition_array['sql'];
            $param_list = array_merge($param_list, $where_condition_array['params']);
        }
        $affected_rows = $this->exec($sql, $param_list);
        return $affected_rows < 0 ? 0 : $affected_rows;
    }
    /**
     * update if exist, or insert
     * @param  array  $data_map
     * @param  array  $where_condition_list
     * @param  bool   $is_force
     * @param  bool   $is_update
     * @return int
     */
    public function save($data_map, $where_condition_list, $is_force = false, &$is_update = null)
    {
        $data_map = $this->filterInputDataMap($data_map);
        if (empty($data_map)) {
            return 0;
        }
        $where_condition_list = empty($where_condition_list) ? $this->pk : $where_condition_list;
        if (empty($where_condition_list)) {
            $where_condition_list = [];
            if (is_array($this->pk)) {
                foreach ($this->pk as $key_column) {
                    if (isset($data_map[$key_column])) {
                        $where_condition_list["{$key_column} = "] = $data_map[$key_column];
                    }
                }
            } elseif (is_string($this->pk) && isset($data_map[$this->pk])) {
                $where_condition_list["{$this->pk} = "] = $data_map[$this->pk];
            }
        }
        if (empty($where_condition_list)) {
            return 0;
        }
        $where_condition_array = $this->createWhereConditionByArray($where_condition_list);
        if (empty($where_condition_array['sql'])) {
            return 0;
        }
        $ct = $this->queryCount('1', $where_condition_array['sql'], $where_condition_array['params']);
        if ($ct > 0) {
            if (!is_null($is_update)) {
                $is_update = true;
            }
            return $this->update($data_map, $where_condition_list);
        }
        if (!is_null($is_update)) {
            $is_update = false;
        }
        if (is_array($this->pk)) {
            $is_force = true;
        }
        return $this->insert($data_map, $is_force, false);
    }
    /**
     * delete
     * @param array $where_condition_list
     * @return int : affected_rows
     */
    public function delete($where_condition_list = [])
    {
        if (empty($where_condition_list)) {
            __add_info(
                sprintf('where condition is empty, blocked for safty: %s', get_class($this)),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_ALERT
            );
            return 0;
        }
        $table                 = $this->getTableName($this->custom_table_param);
        $column_list           = array_keys($params);
        $param_list            = [];
        $sql                   = "DELETE FROM `{$table}`";
        $where_condition_array = $this->createWhereConditionByArray($where_condition_list);
        if (!empty($where_condition_array['sql'])) {
            $sql .= ' WHERE ' . $where_condition_array['sql'];
            $param_list = array_merge($param_list, $where_condition_array['params']);
        }
        $affected_rows = $this->exec($sql, $param_list);
        return $affected_rows < 0 ? 0 : $affected_rows;
    }
    /**
     * Executes the SQL statement and returns all rows.
     * @param $sql string
     * @param $param array
     * @return array
     */
    public function queryAll($sql, $params = [])
    {
        $result                   = $this->db->getCommand($this->table, $this->force_master)->queryAll($sql, $params);
        $this->custom_table_param = null;
        return is_array($result) ? $result : [];
    }
    /**
     * get the first row of the result set
     * @return array
     */
    public function queryRow($sql, $params = [])
    {
        $result                   = $this->db->getCommand($this->table, $this->force_master)->queryRow($sql, $params);
        $this->custom_table_param = null;
        return is_array($result) ? $result : [];
    }
    /**
     * dump all data list (with a max of 10000 rows for error protection)
     * @param  array  $column_list
     * @param  string $order
     * @return array
     */
    public function dumpAllDataList($column_list = [], $order = null)
    {
        return $this->queryAllByWhere(null, null, $column_list, null, 10000);
    }
    /**
     * execute non-select sqls
     * @param string $sql
     * @param array $param
     * @return int: failed when < 0
     */
    public function exec($sql, $params = [], $is_get_last_insert_id = false)
    {
        $affected_rows            = $this->db->getCommand($this->table)->exec($sql, $params, $is_get_last_insert_id);
        $this->custom_table_param = null;
        return $affected_rows;
    }
    /**
     * convert string to quoted string to avoid sql errors
     * return quoted string
     */
    public function quote($string)
    {
        return $this->db->getCommand($this->table)->quote($string);
    }
    /**
     * transactional
     * * ### Example:
     *
     * ```
     * $connection->transactional(function ($connection) {
     *   $connection->newQuery()->delete('users')->execute();
     * });
     * @param  callable $callback
     * @return
     */
    public function transactional(callable $callback)
    {
        return $this->db->getCommand($this->table, true)->transactional($callback);
    }
    /**
     * begin transaction
     * @throws PDOException
     * @return  bool: true when successful
     */
    public function beginTransaction()
    {
        return $this->db->getCommand($this->table, true)->beginTransaction();
    }
    /**
     * commit
     * @throws PDOException
     * @return  bool: true when successful
     */
    public function commit()
    {
        return $this->db->getCommand($this->table, true)->commit();
    }
    /**
     * rollback
     * @throws PDOException
     * @return  bool: true when successful
     */
    public function rollback()
    {
        return $this->db->getCommand($this->table, true)->rollBack();
    }
    /**
     * create sql where by condition list
     * @example:
     *     IN           : array('id in' => array(1,2,34,5))
     *     NOT IN       : array('id not in' => array(1,2,34,5))
     *     IS NULL      : array('id is' => null)
     *     IS NOT NULL  : array('id is not' => null)
     * @param array $where_condition_list
     */
    protected function createWhereConditionByArray($where_condition_list)
    {
        if (empty($where_condition_list)) {
            return ['sql' => '', 'params' => []];
        }
        $sql        = '1';
        $param_list = [];
        foreach ($where_condition_list as $where_condition_key => $where_condition_value) {
            $column = trim(preg_replace('/(`|>|<|=|!| is| not| in)+/i', '', $where_condition_key));
            if (preg_match('/ in/i', $where_condition_key)) {
                // in
                if (empty($where_condition_value)) {
                    continue;
                }

                $where_condition_value = array_unique(array_clear_empty($where_condition_value));
                $sql .= " AND {$where_condition_key} ( '" . implode("','", $where_condition_value) . "')";
            } else {
                $sql .= " AND {$where_condition_key} :where_{$column} ";
                $param_list[":where_{$column}"] = $where_condition_value;
            }
        }
        return ['sql' => $sql, 'params' => $param_list];
    }
    /**
     * create sql and param array by 'AND'
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return boolean
     */
    protected function createSqlParamsByAnd(
        $and_list,
        $not_list = null,
        $column_list = null,
        $order = null,
        $limit = null,
        $offset = null
    ) {
        $is_and_list_not_null     = is_array($and_list) && count($and_list) ? true : false;
        $is_not_and_list_not_null = is_array($not_list) && count($not_list) ? true : false;
        if (!$is_and_list_not_null && !$is_not_and_list_not_null) {
            return false;
        }
        $table       = $this->getTableName($this->custom_table_param);
        $force_index = '';
        if ($this->force_index) {
            $force_index = " FORCE INDEX(`{$this->force_index}`) ";
        }
        $sql = 'SELECT ';
        if (empty($column_list)) {
            $sql .= '*';
        } else {
            $sql .= '`' . implode('`,`', $column_list) . '`';
        }
        $sql .= " FROM `{$table}` {$force_index} WHERE 1 ";
        $params = [];
        if ($is_and_list_not_null) {
            foreach ($and_list as $column => $value) {
                if (preg_match('/ in/i', $column)) {
                    $column_name = preg_replace('/ in/i', '', $column);
                    $column_str  = "'" . implode("','", $value) . "'";
                    $sql .= " AND `{$column_name}` IN ( {$column_str} )";
                    continue;
                }
                $sql .= " AND `{$column}` = :{$column}";
                $params[":{$column}"] = $value;
            }
        }
        if ($is_not_and_list_not_null) {
            foreach ($not_list as $column => $value) {
                if (preg_match('/ in/i', $column)) {
                    $column_name = preg_replace('/ in/i', '', $column);
                    $column_str  = "'" . implode("','", $value) . "'";
                    $sql .= " AND `{$column_name}` NOT IN ( {$column_str} )";
                    continue;
                }
                $sql .= " AND `{$column}` != :{$column}";
                $params[":{$column}"] = $value;
            }
        }
        if ($order) {
            $sql .= " ORDER BY {$order}";
        }
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        return ['sql' => $sql, 'params' => $params];
    }
    /**
     * get select column string
     * @param array $column_list
     */
    protected function getSelectColumnStr(array $column_list)
    {
        if (empty($column_list)) {
            return '';
        }

        $column_list = array_clear_empty($column_list);
        if (empty($column_list)) {
            return '';
        }

        return implode(',', $column_list);
    }
    /**
     * get table name
     */
    protected function getTableName($param = null)
    {
        return $this->table;
    }
    /**
     * filter input data map
     * @param array $data_map
     */
    public function filterInputDataMap($data_map)
    {
        if (empty($this->default_column_list)) {
            return $data_map;
        }

        return filter_array($data_map, $this->default_column_list);
    }
    /**
     * add filter input column
     * @param string $column
     */
    protected function addFilterInputColumn($column)
    {
        if (empty($column) || empty($this->default_column_list)) {
            return;
        }

        $this->default_column_list[] = $column;
    }
    /**
     * remove filter input column
     * @param string $column
     */
    protected function removeFilterInputColumn($column)
    {
        if (empty($column) || empty($this->default_column_list)) {
            return;
        }

        $this->default_column_list = array_flip($this->default_column_list);
        unset($this->default_column_list[$column]);
        $this->default_column_list = array_flip($this->default_column_list);
    }
    /**
     * format result array
     */
    protected function formatResultArray($result_list, $options = [])
    {
        if (empty($result_list)) {
            return $result_list;
        }
        $key_field   = $this->pk;
        $value_field = null;
        if (!empty($options['key_field'])) {
            $key_field = $options['key_field'];
        }
        if (!empty($options['value_field']) && is_string($options['value_field'])) {
            $value_field = $options['value_field'];
        }
        $data_list = [];
        foreach ($result_list as $result) {
            $key = '';
            if (is_string($key_field)) {
                $key = $result[$key_field];
            } elseif (is_array($key_field)) {
                $key = implode('_', filter_array($result, $key_field));
            }
            $data_list[$key] = is_null($value_field) ? $result : $result[$value_field];
        }
        return $data_list;
    }
}
