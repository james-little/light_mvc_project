<?php
namespace core;

use \Application,
    exception\ModelException,
    resource\db\Db,
    exception\DbException,
    info\InfoCollector,
    exception\ExceptionCode;

/**
 * model (DB)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
class Model {

    private $db;
    protected $table;
    protected $default_column_list;
    protected $force_master = false;
    protected $custom_table_param;

    /**
     * __construct
     */
    public function __construct() {

        $db_config = $this->_getConfig();
        if (empty($db_config['tables'][$this->table])) {
            throw new DbException(sprintf('config not defined in database tables section: %s', $this->table),
               ExceptionCode::DB_CONFIG_NOT_EXIST);
        }
        $db_name = $db_config['tables'][$this->table];
        if (empty($db_config['hosts'][$db_name])) {
            throw new DbException(sprintf('db host not defined in hosts section: %s', $db_name),
                ExceptionCode::DB_CONFIG_NOT_EXIST);
        }
        $db_config = $db_config['hosts'][$db_name];
        if(!empty($db_config['privilege'])) {
            $db_config['privilege'] = $db_config['privilege'];
        }
        $this->db = Db::getInstance()->applyConfig($this->table, $db_config);
    }
    /**
     * get solr config
     * @throws ModelException
     */
    protected function _getConfig() {
        $db_config = Application::getConfigByKey('database');
        if (empty($db_config)) {
            $db_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'database.php';
            if (!is_file($db_config)) {
                throw new ModelException(
                    sprintf('config file not exist: %s', $db_config),
                    ExceptionCode::DB_CONFIG_NOT_EXIST);
            }
            $db_config = include($db_config);
            $db_config = $this->overWriteDbConfigForDebugDevice($db_config);
            Application::setConfig('database', $db_config);
        }
        return $db_config;
    }
    /**
     * over write db config for debug device
     * chage database to %dbname%_debug
     */
    private function overWriteDbConfigForDebugDevice($db_config) {
        if (defined('APPLICATION_IS_DEBUG_DEVICE') && APPLICATION_IS_DEBUG_DEVICE) {
            foreach ($db_config['hosts'] as $host_alias => $host_config) {
                // master
                $db_config['hosts'][$host_alias]['servers']['m']['dbname'] .= '_sandbox';
                if (!$db_config['hosts'][$host_alias]['replication']) {
                    continue ;
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
     * query by 'AND', the result would be only the first row
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function queryByAnd($and_list, $not_list = null, $column_list = null,
        $order = null, $offset = null) {
        $sql_params = $this->createSqlParamsByAnd($and_list, $not_list, $column_list, $order, 1, $offset);
        if (!$sql_params) {
            return array();
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
    public function queryAllByAnd(array $and_list, array $not_list = null, array $column_list = null,
         $order = null, $limit = null, $offset = null) {
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
    public function queryCountByAnd(array $and_list, array $not_list = null, $count_column_name = null) {

        $is_and_list_not_null = is_array($and_list) && count($and_list) ? true : false;
        $is_not_and_list_not_null = is_array($not_list) && count($not_list) ? true : false;
        $query = '1 ';
        $params = array();
        if($is_and_list_not_null) {
            foreach($and_list as $column => $value) {
                $query .= " AND {$column} = :{$column}";
                $params[":{$column}"] = $value;
            }
        }
        if($is_not_and_list_not_null) {
            foreach($not_list as $column => $value) {
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
    public function queryCount($column, $query, $params = array()) {
        if (!$column || !is_string($column)) {
            $column = '*';
        }
        $table = $this->getTableName($this->custom_table_param);
        $sql = "SELECT COUNT({$column}) AS ct FROM `{$table}`";
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
     * @param array  $column_list :
     * @return int | array :failed when < 0
     */
    public function queryAllByWhere($where, $params = array(), $column_list = array(),
            $order = null, $limit = null, $offset = null) {

        $table = $this->getTableName($this->custom_table_param);
        $sql = 'SELECT ';
        if (empty($column_list)) {
            $sql .= '*';
        } else {
            $sql .= implode(',', $column_list);
        }
        $sql .= " FROM `{$table}` ";
        if ($where) $sql .= ' WHERE ' . $where;
        if ($order) $sql .= " ORDER BY {$order}";
        if ($limit) {
            if ($offset == null) $offset = 0;
            $sql .= " LIMIT {$offset}, {$limit}";
        }
        return $this->queryAll($sql, $params);
    }
    /**
     * Executes the SQL statement and returns the first row
     * @param string $where
     * @param array $param
     * @param array $column_list
     * @return int | array: failed when < 0
     */
    public function queryByWhere($where, array $params = array(), array $column_list = array(),
        $order = null, $offset = null) {

        $table = $this->getTableName($this->custom_table_param);
        $sql = 'SELECT ';
        if (empty($column_list)) {
            $sql .= '*';
        } else {
            $sql .= implode(',', $column_list);
        }
        $sql .= " FROM `{$table}` ";
        if ($where) $sql .= ' WHERE ' . $where;
        if ($order) $sql .= " ORDER BY {$order} ";
        if ($offset == null) $offset = 0;
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
    public function increment($column_name, $where_condition, $step = 1) {
        if (empty($column_name)) return 0;
        $table = $this->getTableName($this->custom_table_param);
        $sql = "UPDATE `{$table}` SET `{$column_name}` = `{$column_name}` + {$step} ";
        $where_condition_array = $this->createWhereConditionByArray($where_condition);
        $param_list = array();
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
    public function decrement($column_name, $where_condition, $step = 1) {
        if (empty($column_name)) return 0;
        $table = $this->getTableName($this->custom_table_param);
        $sql = "UPDATE `{$table}` SET `{$column_name}` = `{$column_name}` - {$step} ";
        $where_condition_array = $this->createWhereConditionByArray($where_condition);
        $param_list = array();
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
     * @param array $key_column
     * @param boolean $is_force
     * @param boolean $is_ignore
     * @return int: last_insert_id/affected_rows.
     *              affected_rows is returned when last_insert_id is empty
     */
    public function insert($data_map, $key_column = 'id', $is_force = false, $is_ignore = false) {

        $data_map = $this->filterInputDataMap($data_map);
        if (empty($data_map)) return 0;
        $table = $this->getTableName($this->custom_table_param);
        $sql = 'INSERT';
        if($is_ignore) {
            $sql .= ' IGNORE';
        }
        $sql .= " INTO `{$table}` (";
        $column_list = array_keys($data_map);
        foreach ($column_list as $key => $column) {
            if ($key_column && $key_column == $column) {
                if ($is_force) {
                    $sql .= "`{$column}`,";
                }
            } else {
                $sql .= "`{$column}`,";
            }
        }
        $sql = substr($sql, 0, -1);
        $sql .= ') VALUES (';
        foreach ($column_list as $key => $column) {
            if ($key_column && $key_column == $column) {
                if ($is_force) {
                    $sql .= ":{$column},";
                }
            } else {
                $sql .= ":{$column},";
            }
        }
        $sql = substr($sql, 0, -1);
        $sql .= ')';
        $param_list = array();
        foreach ($data_map as $column => $value) {
            if ($key_column && $key_column == $column) {
                if ($is_force) {
                    $param_list[":{$column}"] = $value;
                }
            } else {
                $param_list[":{$column}"] = $value;
            }
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
     * @return int: affected_rows
     */
    public function update($data_map, $where_condition_list = array()) {
        $data_map = $this->filterInputDataMap($data_map);
        if (empty($data_map)) {
            return 0;
        }
        if (empty($where_condition_list)) {
            __add_info(sprintf('where condition is empty, blocked for safety: %s', get_class($this)),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_ALERT);
            return 0;
        }
        $table = $this->getTableName($this->custom_table_param);
        $column_list = array_keys($data_map);
        $param_list = array();
        $sql = " UPDATE `{$table}` SET ";
        foreach ($column_list as $column) {
            $sql .= "`{$column}` = :{$column},";
            $param_list[":{$column}"] = $data_map[$column];
        }
        $sql = substr($sql, 0, -1);
        $where_condition_array = $this->createWhereConditionByArray($where_condition_list);
        if (!empty($where_condition_array['sql'])) {
            $sql .= ' WHERE ' . $where_condition_array['sql'];
            $param_list = array_merge($param_list, $where_condition_array['params']);
        }
        $affected_rows = $this->exec($sql, $param_list);
        return $affected_rows < 0 ? 0 : $affected_rows;
    }
    /**
     * delete
     * @param array $where_condition_list
     * @return int : affected_rows
     */
    public function delete($where_condition_list = array()) {
        if (empty($where_condition_list)) {
            __add_info(sprintf('where condition is empty, blocked for safty: %s', get_class($this)),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_ALERT);
            return 0;
        }
        $table = $this->getTableName($this->custom_table_param);
        $column_list = array_keys($params);
        $param_list = array();
        $sql = "DELETE FROM `{$table}`";
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
    public function queryAll($sql, $params = array()) {
        $result = $this->db->getCommand($this->table, $this->force_master)->queryAll($sql, $params);
        $this->custom_table_param = null;
        return is_array($result) ? $result : array();
    }
    /**
     * get the first row of the result set
     * @return array
     */
    public function queryRow($sql, $params = array()) {
        $result = $this->db->getCommand($this->table, $this->force_master)->queryRow($sql, $params);
        $this->custom_table_param = null;
        return is_array($result) ? $result : array();
    }
    /**
     * dump all data list (with a max of 10000 rows for error protection)
     * @param  array  $column_list
     * @param  string $order
     * @return array
     */
    public function dumpAllDataList($column_list = array(), $order = null) {
        return $this->queryAllByWhere(null, null, $column_list,
            null, 10000);
    }
    /**
     * execute non-select sqls
     * @param string $sql
     * @param array $param
     * @return int: failed when < 0
     */
    public function exec($sql, $params = array(), $is_get_last_insert_id = false) {
        $affected_rows = $this->db->getCommand($this->table)->exec($sql, $params, $is_get_last_insert_id);
        $this->custom_table_param = null;
        return $affected_rows;
    }
    /**
     * convert string to quoted string to avoid sql errors
     * return quoted string
     */
    public function quote($string) {
        return $this->db->getCommand($this->table)->quote($string);
    }
    /**
     * begin transaction
     */
    public function beginTransaction() {
        return $this->db->getCommand($this->table)->beginTransaction();
    }
    /**
     * commit
     */
    public function commit() {
        return $this->db->getCommand($this->table)->commit();
    }
    /**
     * rollback
     */
    public function rollback() {
        return $this->db->getCommand($this->table)->rollBack();
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
    protected function createWhereConditionByArray(array $where_condition_list) {
        if (empty($where_condition_list)) {
            return array('sql' => '', 'params' => array());
        }
        $sql = '1';
        $param_list = array();
        foreach ($where_condition_list as $where_condition_key => $where_condition_value) {
            $column = trim(preg_replace('/(`|>|<|=|!| is| not| in)+/i', '', $where_condition_key));
            if (preg_match('/ in/i', $where_condition_key)) {
                // in
                if (empty($where_condition_value)) continue;
                $where_condition_value = array_unique(array_clear_empty($where_condition_value));
                $sql .= " AND {$where_condition_key} ( '" . implode("','", $where_condition_value) . "')";
            } else {
                $sql .= " AND {$where_condition_key} :where_{$column} ";
                $param_list[":where_{$column}"] = $where_condition_value;
            }
        }
        return array('sql' => $sql, 'params' => $param_list);
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
        $and_list, $not_list = null, $column_list = null, $order = null, $limit = null, $offset = null
    ) {
        $is_and_list_not_null = is_array($and_list) && count($and_list) ? true : false;
        $is_not_and_list_not_null = is_array($not_list) && count($not_list) ? true : false;
        if(!$is_and_list_not_null && !$is_not_and_list_not_null) {
            return false;
        }
        $table = $this->getTableName($this->custom_table_param);
        $sql = 'SELECT ';
        if (empty($column_list)) {
            $sql .= '*';
        }else{
            $sql .= implode(',', $column_list);
        }
        $sql .= " FROM `{$table}` WHERE 1 ";
        $params = array();
        if($is_and_list_not_null) {
            foreach($and_list as $column => $value) {
                $sql .= " AND {$column} = :{$column}";
                $params[":{$column}"] = $value;
            }
        }
        if($is_not_and_list_not_null) {
            foreach($not_list as $column => $value) {
                $sql .= " AND {$column} != :{$column}";
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
        return array('sql' => $sql, 'params' => $params);
    }
    /**
     * get select column string
     * @param array $column_list
     */
    protected function getSelectColumnStr(array $column_list) {
        if(empty($column_list)) return '';
        $column_list = array_clear_empty($column_list);
        if(empty($column_list)) return '';
        return implode(',', $column_list);
    }
    /**
     * get table name
     */
    protected function getTableName($param = null) {
        return $this->table;
    }
    /**
     * filter input data map
     * @param array $data_map
     */
    public function filterInputDataMap($data_map) {
        if(empty($this->default_column_list)) return $data_map;
        return filter_array($data_map, $this->default_column_list);
    }
    /**
     * add filter input column
     * @param string $column
     */
    protected function addFilterInputColumn($column) {
        if (empty($column) || empty($this->default_column_list)) return ;
        $this->default_column_list[] = $column;
    }
    /**
     * remove filter input column
     * @param string $column
     */
    protected function removeFilterInputColumn($column) {
        if (empty($column) || empty($this->default_column_list)) return ;
        $this->default_column_list = array_flip($this->default_column_list);
        unset($this->default_column_list[$column]);
        $this->default_column_list = array_flip($this->default_column_list);
    }
}