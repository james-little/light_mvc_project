<?php
namespace resource\db\command;

use exception\ExceptionCode;
use \ClassLoader,
    \ErrorCode,
    \Application,
    \Exception,
    Monitor,
    resource\ResourcePool,
    exception\DbException,
    info\InfoCollector,
    resource\db\driver\DbDriverMysql,
    resource\db\driver\DbDriverInterface,
    resource\db\DbPrivilege,
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

    const METHOD_QUERY_ALL = 1;
    const METHOD_QUERY_ROW = 2;
    const METHOD_QUERY_COLUMN = 3;
    const METHOD_EXEC = 4;

    protected static $_instance;
    private $driver;
    private $config;
    private $privilege;
    protected $_log;
    protected $_log_dir;
    protected $_mode;
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
    public static function getInstance() {
        if (self::$_instance !== null) {
            return self::$_instance;
        }
        self::$_instance = new self();
        return self::$_instance;
    }
    /**
     * set database privilege
     * @param string $privilege_class
     */
    public function applyPrivilege($privilege_class) {
        $this->privilege = ClassLoader::loadClass($privilege_class);
        return $this;
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
        $log_file = make_file($base_dir, $this->_mode);
        if (substr($log_file, 0, 1) != '/') {
            $log_file = '/tmp/' . Application::getProjectName() . '_' . APPLICATION_ENVI . "_mysql_{$sub_category}_{$log_file}";
        }
        return $log_file;
    }
    /**
     * write log to file in different log mode
     * @param string $message
     * @throws DbException
     */
    protected function log($message, $sub_category = null) {
        if (!$this->is_log_enabled) {
            return ;
        }
        if(empty($message)) {
            return;
        }
        if (!$this->_log) {
            $this->_log = ClassLoader::loadClass('\log\Log');
        }
        $file_name = $this->getLogFilePath($sub_category);
        $this->_log->setWriter(new LogWriterStream($file_name));
        $message = '[' . date('Y/m/d H:i:s') . ']' . $message;
        $this->_log->log($message);
    }
    /**
     * Executes the SQL statement and returns all rows.
     * @param $sql string
     * @param $param array
     * @param $fetch_associative bool:
     *     if you want to get the row(s) with result's key named the same as it's
     *     defined in the database, set it to true
     * @return int | array: failed when < 0
     */
    public function queryAll($sql, $param = array(), $fetch_associative = true) {
        $arg = array();
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query(self::METHOD_QUERY_ALL, $sql, $param, $arg);
    }
    /**
     * get the first row of the result set
     * @param string $sql
     * @param array $param
     * @param boolean $fetch_associative
     * @return int | array: failed when < 0
     */
    public function queryRow($sql, $param = array(), $fetch_associative = true) {
        $arg = array();
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query(self::METHOD_QUERY_ROW, $sql, $param, $arg);
    }
    /**
     * Executes the SQL statement and returns the {0_based_index} column of the result set.
     * @param string $sql
     * @param array $param
     * @param int $column_index
     * @return int | array: failed when < 0
     *     - the first column of the query result. Empty array if no result.
     */
    public function queryColumn($sql, $param = array(), $column_index) {
        $arg = array();
        $arg['column_index'] = $column_index;
        return $this->query(self::METHOD_QUERY_COLUMN, $sql, $param, $arg);
    }
    /**
     * exec non-query commands like insert, delete...
     * return int
     *     failed when < 0
     *     INSERT : last_insert_id/affected_rows
     *     UPDATE : affected_rows
     *     OTHER  : 1 - success
     */
    public function exec($sql, $param = array(), $is_get_last_insert_id = false) {
        $arg = array();
        $arg['is_get_last_insert_id'] = (bool)$is_get_last_insert_id;
        return $this->query(self::METHOD_EXEC, $sql, $param, $arg);
    }
    /**
     * convert string to quoted string to avoid sql errors
     * @param string $string
     * @return string
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
     * @return array | int
     *     failed when < 0
     *     SELECT : array
     *     INSERT : last_insert_id/affected_rows
     *     UPDATE : affected_rows
     *     OTHER  : 1 - success
     */
    private function query($method, $sql, $param, $arg = null) {

        if (!empty($this->config['is_use_socket'])) {
            return $this->callSocketClient($method, $sql, $param);
        }
        $sql = trim($sql);
        if($this->privilege && !$this->privilege->checkExcutable($sql)) {
            __add_info(
                    'db_command_query#unable to execute command',
                    InfoCollector::TYPE_LOGIC,
                    InfoCollector::LEVEL_DEBUG
            );
            return ErrorCode::ERROR_DB_SQL_NOT_ALLOWED;
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
                    case self::METHOD_EXEC:
                        $result = $this->driver->exec($sql, $param, $arg['is_get_last_insert_id']);
                        break;
                    case self::METHOD_QUERY_COLUMN:
                        $result = $this->driver->queryColumn($sql, $param, $arg['column_index']);
                        break;
                    case self::METHOD_QUERY_ROW:
                        $result = $this->driver->queryRow($sql, $param, $arg['fetch_associative']);
                        break;
                    case self::METHOD_QUERY_ALL:
                        $result = $this->driver->queryAll($sql, $param, $arg['fetch_associative']);
                        break;
                    default:
                        return array();
                }
                $execute_time = Monitor::stop();
                $this->logSlowQuery($sql, $execute_time);
                __add_info(
                    sprintf(
                        'db_command_query#execute_time:[%s] %s #params: %s',
                        $execute_time, $sql, var_export($param, true)
                    ),
                    InfoCollector::TYPE_LOGIC,
                    InfoCollector::LEVEL_DEBUG
                );
                break;
            } catch (DbException $e) {
                $this->log($this->getExceptionMessage($e, $sql, $max_try_time, $param));
                $resource_pool->unregisterResource('db', $resource_key);
                __add_info(
                    sprintf(
                        'db_command_query#kill db connection %s for exception: %s. sql:%s',
                        $db_config['host'], $e->getMessage(), $sql
                    ),
                    InfoCollector::TYPE_LOGIC,
                    InfoCollector::LEVEL_DEBUG
                );
                // get result error by exception code
                $result = $this->getErrorResultByException($e);
                sleep(1);
            }
            $max_try_time --;
        }
        return $result;
    }
    /**
     * get exception message
     * @param Exception $e
     * @param string $sql
     * @param int $max_try_time
     * @param array $param
     * @return string
     */
    private function getExceptionMessage(Exception $e, $sql, $max_try_time, $param) {
        $message = '';
        if ($max_try_time) {
            $message .= "retry{$max_try_time}";
        }
        $message .= 'failed:'.$e->getMessage() . '#' . $sql . '#';
        foreach ($param as $param_key => $param_value) {
            $message .= "{$param_key} : {$param_value},";
        }
        $message .= "\n";
        return $message;
    }
    /**
     * get error result by exception
     * @param Exception $e
     * @return int
     */
    private function getErrorResultByException(Exception $e) {
        $exception_code = $e->getCode();
        $error_code = 0;
        switch ($exception_code) {
            case ExceptionCode::DB_EXECUTE_ERROR:
                $error_code = ErrorCode::ERROR_DB_EXECUTE_ERROR;
                break;
            case ExceptionCode::DB_ACCESS_OBJ_ERROR:
                $error_code = ErrorCode::ERROR_DB_ACCESS_OBJ_ERROR;
                break;
            case ExceptionCode::DB_FETCH_ERROR:
                $error_code = ErrorCode::ERROR_DB_FETCH_ERROR;
                break;
            default:
                $error_code = ErrorCode::ERROR_DB_DEFAULT;
                break;
        }
        return $error_code;
    }
    /**
     * get db config by sql
     * @param string $sql
     */
    protected function getDbConfig($sql) {
        if (!$this->config['replication']) {
            __add_info('db_command#replication off, master selected',
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return $this->config['servers']['m'];
        }
        if (strtolower(substr($sql, 0, 6)) == 'select' || strtolower(substr($sql, 0, 17)) == 'show create table') {
            if($this->config['force_master']) {
                // force return master
                return $this->config['servers']['m'];
            }
            if (empty($this->config['servers']['s'])) {
                // return master if slave is empty
                return $this->config['servers']['m'];
            }
            $slave_id_list = array_keys($this->config['servers']['s']);
            // also do select in master, too
            $slave_id_list[] = 'm';
            $slave_id = $this->getRandomSlaveId($slave_id_list);
            __add_info('db_command#replication on, slave selected: ' . $slave_id,
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            if ($slave_id == 'm') return $this->config['servers']['m'];
            return $this->config['servers']['s'][$slave_id];
        }
        __add_info('db_command#replication on, not select query, master selected',
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
        $this->setLogDir(Application::getLogBaseDir() . '/mysql' . $db_config['log']['log_dir']);
        // set user customized log class
        if (!empty($db_config['log']['log_class'])) {
            if (!class_exists($db_config['log']['log_class'])) {
                __add_info('db_command#can not find log class ' . $db_config['log']['log_class'] .', use default',
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $this->_log = ClassLoader::loadClass('\log\Log');
            } else {
                $db_command_logger = ClassLoader::loadClass($db_config['log']['log_class']);
                $this->setLog($db_command_logger);
            }
        } else {
            $this->_log = ClassLoader::loadClass('\log\Log');
        }
        $this->_mode = empty($db_config['log']['mode']) ? 'daily' : $db_config['log']['mode'];
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
            $message = 'cost:'.$execute_time . '#' . $sql . "\n";
            $this->log($message, 'Slow_Query');
        }
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

