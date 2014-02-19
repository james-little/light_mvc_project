<?php
namespace resource\db\command;

use ClassLoader,
    Monitor,
    resource\ResourcePool,
    exception\DbException,
    info\InfoCollector,
    resource\db\driver\DbDriverMysql,
    resource\db\driver\DbDriverInterface,
    log\LogInterface,
    log\writer\LogWriterStream;

/**
 * DB command class
 * =======================================================
 * This class is for execute sql command(s).
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package resource\db\command
 * @version 1.0
 **/
class DbCommand {

    protected static $_instance;

    private $driver;
    private $config;
    protected $_log;
    protected $_log_dir;
    private $is_log_enabled = false;
    private $_slow_query_time_limit;

    /**
     * Constructor.
     */
    protected function __construct() {}
    /**
     * singleton
     * @param DbDriverInterface $driver
     * @param array $config
     * @return DbCommand
     */
    static public function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * apply driver
     * @param DbDriverInterface $driver
     * @param array | null $config
     */
    public function applyDriver(DbDriverInterface $driver, $config = null) {
        $this->driver = $driver;
        $this->applyConfig($config);
        return $this;
    }
    /**
     * get driver
     */
    public function getDriver() {
        return $this->driver;
    }
    /**
     * set command config
     * @param array $config
     */
    private function applyConfig($config) {
        if (empty($config)) return ;
        $this->config = $config;
    }
    /**
     * set driver to null when sleep
     */
    public function __sleep() {
        $this->driver = null;
    }
    /**
     * log setter
     * @param LogInterface $log
     */
    public function setLog(LogInterface $log) {
        $this->_log = $log;
    }
    /**
     * log getter
     * @return LogInterface $log
     */
    public function getLog() {
        return $this->_log;
    }
    /**
     * log_dir setter
     * @param string $log_dir
     */
    public function setLogDir($log_dir) {
        $this->_log_dir = $log_dir;
    }
    /**
     * log_dir getter
     * @return string log_dir
     */
    public function getLogDir() {
        return $this->_log_dir;
    }
    /**
     * is_log_enabled setter
     * @param bool $is_log_enabled
     */
    public function setIsLogEnabled($is_log_enabled) {
        $this->is_log_enabled = $is_log_enabled;
    }
    /**
     * is_log_enabled getter
     * @return bool is_log_enabled
     */
    public function getIsLogEnabled() {
        return $this->is_log_enabled;
    }
    /**
     * get file path of the log file. create log folder if not exist
     * @param string | null $sub_category
     */
    protected function getLogFilePath($sub_category = null) {

        if (!$sub_category || !is_string($sub_category)) {
            $sub_category = '';
        }
        $base_dir = $this->_log_dir;
        if ($sub_category) {
            $base_dir .= "/{$sub_category}";
        }
        $log_file = make_file($base_dir, 'daily');
        if (!preg_match('#^/#', $log_file)) {
            $log_file = '/tmp/light_mvc_' . "{$sub_category}_{$log_file}";
        }
        return $log_file;
    }
    /**
     * write log to file in different log mode
     * @param string $message
     * @throws DbException
     */
    public function log($message, $sub_category = null) {
        if (!$this->_log) {
            $this->_log = ClassLoader::loadClass('\log\Log');
        }
        if (!$this->_log->getWriter()) {
            $file_name = $this->getLogFilePath($sub_category);
            $this->_log->setWriter(new LogWriterStream($file_name));
        }
        $this->_log->log($message);
    }
    /**
     * get count of the rows you want to query
     * Example:
     *
     *     $query = ' user_id > :user_id AND id >= :id';
     *     $param = array(':user_id' => 1, ':id' => 1000);
     *     $this->queryCount('id', 'some_table', $query, $param);
     *
     * @param $column string
     * @param $table string
     * @param $query string
     * @param $param array
     * @return bool | array
     */
    public function queryCount($column, $table, $query, $param = array()) {
        if (!$table) {
            return false;
        }
        if (!$column || !is_string($column)) {
            $column = '*';
        }
        $sql = "SELECT COUNT({$column}) AS ct FROM `{$table}`";
        if (!empty($query)) {
            $sql .= " WHERE {$query} ";
        }
        $result = $this->queryRow($sql, $param);
        if (empty($result)) {
            return false;
        }
        return $result['ct'];
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
    public function queryAll($sql, $param = array(), $fetch_associative = true) {
        $arg = array();
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query('queryAll', $sql, $param, $arg);
    }

    /**
     * get the first row of the result set
     * @return  false when failed
     */
    public function queryRow($sql, $param = array(), $fetch_associative = true) {
        $arg = array();
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query('queryRow', $sql, $param, $arg);
    }
    /**
     * Executes the SQL statement and returns the {0_based_index} column of the result set.
     * @return array the first column of the query result. Empty array if no result.
     */
    public function queryColumn($sql, $param = array(), $column_index) {
        $arg = array();
        $arg['column_index'] = $column_index;
        return $this->query('queryColumn', $sql, $param, $arg);
    }
    /**
     * exec non-query commands like insert, delete...
     * return int|bool
     *     insert : last_insert_id
     *     update: affected_row
     */
    public function exec($sql, $param = array(), $is_get_last_insert_id = false) {
        $arg = array();
        $arg['is_get_last_insert_id'] = (bool)$is_get_last_insert_id;
        return $this->query('exec', $sql, $param, $arg);
    }
    /**
     * convert string to quoted string to avoid sql errors
     * return quoted string
     */
    public function quote($string) {
        return $this->driver->quote($string);
    }
    /**
     * execute query
     * @param string $method
     * @param string $sql
     * @param array $param
     * @param array $arg
     * @return boolean|array:
     */
    private function query($method, $sql, $param, $arg = null) {

        $sql = trim($sql);
        if (!$this->isAbleToExecute($sql)) {
            __add_info(
                __message('db_command_query#unable to execute command'),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        if (!$this->validateMethod($method)) {
            __add_info(
                __message('db_command_query#unable to execute method'),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        if (empty($param)) {
            $param = array();
        }
        $result = null;
        $max_try_time = 3;
        $db_config = $this->getDbConfig($sql);
        $db_config = $this->fillDbConfigDefault($db_config);
        $this->applyLogConfig($db_config);
        $resource_pool = ResourcePool::getInstance();
        $resource_key = $resource_pool->getResourceKey(get_class($this->driver), $db_config);
        while ($max_try_time) {
            try {
                $connection = $resource_pool->getResource('db', $resource_key);
                if (!$connection) {
                    $connection = $this->driver->getConnection($db_config);
                    $resource_pool->registerResource('db', $resource_key, $connection);
                }
                $this->driver->bindConnection($connection);
                Monitor::reset();
                switch ($method) {
                    case 'exec':
                        $result = $this->driver->exec($sql, $param, $arg['is_get_last_insert_id']);
                        break;
                    case 'queryColumn':
                        $result = $this->driver->queryColumn($sql, $param, $arg['column_index']);
                        break;
                    case 'queryRow':
                        $result = $this->driver->queryRow($sql, $param, $arg['fetch_associative']);
                        break;
                    case 'queryAll':
                        $result = $this->driver->queryAll($sql, $param, $arg['fetch_associative']);
                        break;
                    default:
                        return array();
                }
                $execute_time = Monitor::stop();
                $this->logSlowQuery($sql, $execute_time);
                __add_info(
                    __message(
                        'db_command_query#execute_time:[%s] %s #params: %s',
                        array($execute_time, $sql, var_export($param, true))
                    ),
                    InfoCollector::TYPE_LOGIC,
                    InfoCollector::LEVEL_DEBUG
                );
                break;
            } catch (DbException $e) {
                if ($this->is_log_enabled) {
                    $message = '[' . date('Y-m-d H:i:s') . ']';
                    if ($max_try_time) {
                        $message .= "retry{$max_try_time}";
                    }
                    $message .= 'failed:'.$e->getMessage() . '#' . $sql . '#';
                    foreach ($param as $param_key => $param_value) {
                        $message .= "{$param_key} : {$param_value},";
                    }
                    $message .= "\n";
                    $this->log($message);
                }
                $resource_pool->unregisterResource('db', $resource_key);
                __add_info(
                    __message(
                        'db_command_query#kill db connection %s for exception: %s',
                        array($db_config['host'], $e->getMessage())
                    ),
                    InfoCollector::TYPE_LOGIC,
                    InfoCollector::LEVEL_DEBUG
                );
                sleep(1);
                $result = false;
            }
            $max_try_time --;
        }
        return $result;
    }
    /**
     * get db config by sql
     * @param string $sql
     */
    protected function getDbConfig($sql) {
        if (!$this->config['replication']) {
            __add_info(__message('db_command#replication off, master selected'),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return $this->config['servers']['m'];
        }
        if (preg_match('#^select#i', $sql)) {
            if (empty($this->config['servers']['s'])) {
                return $this->config['servers']['m'];
            }
            $slave_id_list = array_keys($this->config['servers']['s']);
            $slave_id = $this->getRandomSlaveId($slave_id_list);
            __add_info(__message('db_command#replication on, slave selected:' . $slave_id),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return $this->config['servers']['s'][$slave_id];
        }
        __add_info(__message('db_command#replication on, not select query, master selected'),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        return $this->config['servers']['m'];
    }
    /**
     * fill db config with default value
     * @param array $db_config
     * @return array
     */
    protected function fillDbConfigDefault($db_config) {
        $db_config['port'] = empty($db_config['port']) ? 3306 : $db_config['port'];
        return $db_config;
    }
    /**
     * get random slave id
     * @param array $slave_id_list
     * @return int
     */
    protected function getRandomSlaveId($slave_id_list) {
        if (empty($slave_id_list)) return null;
        $count = count($slave_id_list);
        $total = 100;
        $step = $total / $count;
        $slave_id_list_tmp = array();
        for ($i = 0; $i < $count; $i++) {
            $slave_id_list_tmp[$slave_id_list[$i]] = $i < $count - 1 ? $step : $total - $step * $i;
        }
        $weight = 0;
        $tempdata = array();
        foreach ($slave_id_list_tmp as $slave_id => $percent) {
            $weight += $percent;
            for ($i = 0; $i < $percent; $i++) {
                $tempdata[] = $slave_id;
            }
        }
        $use = rand(0, $weight - 1);
        return $tempdata[$use];
    }
    /**
     * apply log config settings
     * @param array $db_config
     */
    private function applyLogConfig($db_config) {
        if (isset($db_config['log']['enabled'])) {
            $this->setIsLogEnabled($db_config['log']['enabled']);
        }
        if (!$this->getIsLogEnabled()) {
            return ;
        }
        if (isset($db_config['log']['log_dir'])) {
            $this->setLogDir($db_config['log']['log_dir']);
        }
        // set user customized log class
        if (!empty($db_config['log']['log_class'])) {
            if (!class_exists($db_config['log']['log_class'])) {
                __add_info(__message('db_command#can not find log class ' . $db_config['log']['log_class'] .', use default'),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $this->_log = ClassLoader::loadClass('\log\Log');
            } else {
                $db_command_logger = ClassLoader::loadClass($db_config['log']['log_class']);
                $this->setLog($db_command_logger);
            }
        } else {
            $this->_log = ClassLoader::loadClass('\log\Log');
        }
        $this->_slow_query_time_limit = $db_config['log']['slow_query'];
    }

    /**
     * log slow query. if you set db_config's slow query to 0
     * that means shut off the slow query log
     * @param string $sql
     * @param float $execute_time
     */
    private function logSlowQuery($sql, $execute_time) {
        if (empty($this->_slow_query_time_limit)) {
            return ;
        }
        if ($execute_time >= $this->_slow_query_time_limit) {
            $message = '[' . date('Y-m-d H:i:s') . ']cost:'.$execute_time . '#' . $sql . "\n";
            $this->log($message, 'Slow_Query');
        }
    }
    /**
     * limits the sql execute
     * @param string $sql
     */
    protected function isAbleToExecute($sql) {
        return true;
        /*
        if (strpos($sql, 'INSERT') !== false || strpos($sql, 'UPDATE') !== false || strpos($sql, 'DELETE') !== false) {
            // insert/update/delete is forbidden for outsider ips
            if (!James_Controller_Request_Http::getIsInsideIP()) {
                return false;
            }
        }
        return true;
        */
    }
    /**
     * validate if the method can be invoke
     * @param string $method
     * @return boolean
     */
    private function validateMethod($method) {
        $method_list = array('queryColumn' => 0, 'queryAll' => 0, 'queryRow' => 0, 'exec' => 0);
        if (!isset($method_list[$method])) {
            return false;
        }
        return true;
    }
    /**
     * begin transaction
     */
    public function beginTransaction() {
        return $this->dirver->beginTransaction();
    }
    /**
     * commit
     */
    public function commit() {
        return $this->dirver->commit();
    }
    /**
     * rollback
     */
    public function rollback() {
        return $this->dirver->rollBack();
    }
}

