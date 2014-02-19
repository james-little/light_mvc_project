<?php
namespace core;

use exception\DbException;

use resource\db\Db,
    info\InfoCollector,
    exception\ExceptionCode,
    exception\AppException;

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

    /**
     * __construct
     */
    public function __construct() {
        $db_config = $this->_getDbConfig();
        if (empty($db_config['tables'][$this->table])) {
            throw new DbException(__message('config not defined in database tables section: %s', array($this->table)));
        }
        $db_name = $db_config['tables'][$this->table];
        if (empty($db_config['hosts'][$db_name])) {
            throw new DbException(__message('db host not defined in hosts section: %s', array($db_name)));
        }
        $db_config = $db_config['hosts'][$db_name];
        $this->db = Db::getInstance()->applyConfig($db_config);
    }
    /**
     * get db config
     * @throws AppException
     */
    protected function _getDbConfig() {
        $db_config = \AppRuntimeContext::getInstance()->getData('db_config');
        if (empty($db_config)) {
            $db_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DIRECTORY_SEPARATOR . 'database.php';
            if (!file_exists($db_config)) {
                throw new AppException(
                    __message('config file not exist: %s',array($db_config)),
                    ExceptionCode::BUSINESS_DB_CONFIG_NOT_EXIST);
            }
            $db_config = include($db_config);
            \AppRuntimeContext::getInstance()->setData('db_config', $db_config);
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
     * @return boolean
     */
    public function queryByAnd(
        $and_list, $not_list = null, $column_list = null, $order = null, $limit = null, $offset = null
    ) {
        $sql_params = $this->createSqlParamsByAnd($and_list, $not_list, $column_list, $order, $limit, $offset);
        if (!$sql_params) {
            return array();
        }
        return $this->queryRow($sql_params['sql'], $sql_params['params']);
    }
    /**
     * query count by and
     * @param array $and_list
     * @param array $not_list
     * @param string $count_column_name
     */
    public function queryCountByAnd($and_list, $not_list = null, $count_column_name = null) {

        $is_and_list_not_null = is_array($and_list) && count($and_list) ? true : false;
        $is_not_and_list_not_null = is_array($not_list) && count($not_list) ? true : false;
        $query = " 1 ";
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
     * query by 'AND'. return all the rows match the condition
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return boolean
     */
    public function queryAllByAnd(
        $and_list, $not_list = null, $column_list = null, $order = null, $limit = null, $offset = null
    ) {
        $sql_params = $this->createSqlParamsByAnd($and_list, $not_list, $column_list, $order, $limit, $offset);
        if (!$sql_params) {
            return false;
        }
        return $this->queryAll($sql_params['sql'], $sql_params['params']);
    }
    /**
     * set the isForce flag true if you want to insert a record with nothing
     * @param array $params
     * @param array $key_column
     * @param bool $is_force
     * @return false if failed
     */
    public function insert($params, $key_column = 'id', $is_force = false, $is_ignore = false) {

        if (empty($params)) {
            return false;
        }
        $sql = 'INSERT';
        if($is_ignore) {
            $sql .= ' IGNORE';
        }
        $sql .= " INTO `{$this->table}` (";
        $column_list = array_keys($params);
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
        foreach ($params as $column => $value) {
            if ($key_column && $key_column == $column) {
                if ($is_force) {
                    $param_list[":{$column}"] = $value;
                }
            }else{
                $param_list[":{$column}"] = $value;
            }
        }
        return $this->exec($sql, $param_list, true);
    }

    /**
     * @param array $params
     * @param array $where_condition_list
     */
    public function update($params, $where_condition_list = array()) {

        if (empty($params) || !is_array($where_condition_list)) {
            return false;
        }
        $column_list = array_keys($params);
        $param_list = array();
        $sql = " UPDATE `{$this->table}` SET ";
        foreach ($column_list as $column) {
            $sql .= "`{$column}` = :{$column},";
            $param_list[":{$column}"] = $params[$column];
        }
        $sql = substr($sql, 0, -1);
        if (!empty($where_condition_list)) {
            $sql .= ' WHERE 1 ';
            foreach ($where_condition_list as $where_condition_key => $where_condition_value) {
                $column = trim(preg_replace('/(`|>|<|=|!| is| not| in)+/i', '', $where_condition_key));
                if (preg_match('/ in/i', $where_condition_key)) {
                    // in
                    if (!is_array($where_condition_value) || empty($where_condition_value)) continue;
                    $where_condition_value = array_clear_empty($where_condition_value);
                    $sql .= " AND {$where_condition_key} ( '" . implode("','", $where_condition_value) . "')";
                } else {
                    $sql .= " AND {$where_condition_key} :where_{$column} ";
                    $param_list[":where_{$column}"] = $where_condition_value;
                }
            }
        }
        return intval($this->exec($sql, $param_list));
    }
    /**
     * query count number by column name
     * @param string $column
     * @param string $query
     * @param array $param
     * @return boolean|\logic\adReward\model\false
     */
    public function queryCount($column, $query, $param = array()) {
        return intval($this->db->getCommand()->queryCount($column, $this->table, $query, $param));
    }
    /**
     * Executes the SQL statement and returns all rows.
     * @param $sql string
     * @param $param array
     * @param $fetch_associative bool:
     *     if you want to get the row(s) with result's key named the same as it's
     *     defined in the database, set it to true
     * @return bool | array
     */
    public function queryAll($sql, $param = array()) {
        $result = $this->db->getCommand()->queryAll($sql, $param);
        $result = empty($result) ? array() : $result;
        return $result;
    }

    /**
     * get the first row of the result set
     * @return  false when failed
     */
    public function queryRow($sql, $param = array()) {
        $result = $this->db->getCommand()->queryRow($sql, $param);
        $result = empty($result) ? array() : $result;
        return $result;
    }
    /**
     * @param string $sql
     * @param array $param
     */
    public function exec($sql, $param = array(), $is_get_last_insert_id = false) {
        return $this->db->getCommand()->exec($sql, $param, $is_get_last_insert_id);
    }
    /**
     * convert string to quoted string to avoid sql errors
     * return quoted string
     */
    public function quote($string) {
        return $this->db->getCommand()->quote($string);
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
        $sql = 'SELECT ';
        if (empty($column_list)) {
            $sql .= '*';
        }else{
            $sql .= implode(',', $column_list);
        }
        $sql .= " FROM `{$this->table}` WHERE 1 ";
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
}