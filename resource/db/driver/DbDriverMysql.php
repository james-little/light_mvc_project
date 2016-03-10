<?php
namespace resource\db\driver;

use resource\db\driver\DbDriverInterface,
    exception\DbException,
    info\InfoCollector,
    resource\ResourcePool,
    exception\ExceptionCode,
    Exception,
    PDO,
    PDOStatement;

/**
 * mysql driver class
 * =======================================================
 * mysql implementation of ad reward db driver
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package resource\db\driver
 * @version 1.0
 **/


class DbDriverMysql implements DbDriverInterface {

    const METHOD_QUERY_ALL = 1;
    const METHOD_QUERY_ROW = 2;
    const METHOD_QUERY_COLUMN = 3;
    const METHOD_EXEC = 4;
    const RESOURCE_TYPE = 'pdo_mysql';

    private $pdo;
    private $statement;
    private static $map = array(
        'boolean' => PDO::PARAM_BOOL,
        'integer' => PDO::PARAM_INT,
        'string' => PDO::PARAM_STR,
        'NULL' => PDO::PARAM_NULL,
    );

    /*
     * set pdo to null when destruct
     */
    public function __destruct() {
        $this->pdo = null;
    }
    /**
     * __clone
     */
    public function __clone() {
        $this->pdo = null;
    }

    /**
     * Executes the SQL statement and returns all rows.
     * when query_cache is needed,  then $isUseCache must be set to true
     */
    public function queryAll($sql, $param = array(), $fetch_associative = true) {
        $arg = array();
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query(self::METHOD_QUERY_ALL, $sql, $param, $arg);
    }
    /**
     * get the first row of the result set
     * when query_cache is needed,  then $isUseCache must be set to true
     * @return  false when failed
     */
    public function queryRow($sql, $param = array(), $fetch_associative = true) {
        $arg = array();
        $arg['fetch_associative'] = $fetch_associative;
        return $this->query(self::METHOD_QUERY_ROW, $sql, $param, $arg);
    }
    /**
     * Executes the SQL statement and returns the {0_based_index} column of the result set.
     * @return array the first column of the query result. Empty array if no result.
     */
    public function queryColumn($sql, $param = array(), $column_index) {
        $arg = array();
        $arg['column_index'] = $column_index;
        return $this->query(self::METHOD_QUERY_COLUMN, $sql, $param, $arg);
    }
    /**
     * exec non-query commands like insert, delete...
     * return affected rows
     */
    public function exec($sql, $param = array(), $is_get_last_insert_id = false) {
        $arg = array();
        $arg['get_last_insert_id'] = (bool) $is_get_last_insert_id;
        return $this->query(self::METHOD_EXEC, $sql, $param, $arg);
    }
    /**
     * convert string to quoted string to avoid sql errors
     * return quoted string
     */
    public function quote($string) {
        return $this->pdo->quote($string);
    }
    /**
     * open database connection
     */
    public function bindConnection($connection) {
        if (!$connection instanceof \PDO) {
            throw new DbException(
                sprintf('connection type not match. needed: PDO, given: %s', get_class($connection)),
                ExceptionCode::DB_ACCESS_OBJ_ERROR);
        }
        $this->pdo = $connection;
    }
    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    private function close() {
        $this->pdo = null;
    }
    /**
     * Determines the PDO type for the specified PHP type.
     * @param string The PHP type (obtained by gettype() call).
     * @return integer the corresponding PDO type
     */
    private function getPdoType($type) {
        return isset(self::$map[$type]) ? self::$map[$type] : PDO::PARAM_STR;
    }
    /**
     * bind params to pdo statement
     * @param string $name
     * @param string $value
     * @param string $data_type
     * @param int $length
     */
    private function bindParam($name, &$value, $data_type = null) {

        if($data_type === null) {
            $data_type = $this->getPdoType(gettype($value));
        }
        $this->statement->bindParam($name, $value, $data_type);
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
     * @param string method of PDOStatement to be called
     * @return array | int
     *     SELECT : array
     *     INSERT : last_insert_id/affected_rows. affected_rows is returned
     *              when last_insert_id is empty
     *     UPDATE : affected_rows
     * @throws DbException
     */
    private function query($method, $sql, $param = array(), $arg = array()) {

        if (!$this->pdo instanceof PDO) {
            throw new DbException('pdo not set', ExceptionCode::DB_ACCESS_OBJ_ERROR);
        }
        $result = null;
        if (empty($param)) {
            $param = array();
        }
        try {
            $this->statement = $this->pdo->prepare($sql);
            if($method != self::METHOD_EXEC) {
                if (isset($arg['fetch_associative'])) {
                    $this->statement->setFetchMode(PDO::FETCH_ASSOC);
                } else {
                    if ($method != self::METHOD_QUERY_COLUMN) {
                        $this->statement->setFetchMode(PDO::FETCH_NUM);
                    }
                }
            }
            if($param !== array()) {
                foreach($param as $key => $val) {
                    $this->bindParam($key, $param[$key]);
                }
                $val = null;
            }
            $is_success = $this->statement->execute();
            if (!$is_success) {
                $last_error_code = $this->statement->errorCode();
                $last_error_info = $this->statement->errorInfo();
                throw new DbException(
                    sprintf('pdo execute error[ %s ]: %s; sql:%s', $last_error_code, $last_error_info['2'], $sql),
                    ExceptionCode::DB_EXECUTE_ERROR
                );
            }
            switch ($method) {
                case self::METHOD_QUERY_COLUMN:
                    $column_index = isset($arg['column_index']) ? $arg['column_index'] : 0;
                    $result = $this->statement->fetchAll(PDO::FETCH_COLUMN, $column_index);
                    break;
                case self::METHOD_QUERY_ALL:
                    $result = $this->statement->fetchAll();
                    break;
                case self::METHOD_QUERY_ROW:
                    $result = $this->statement->fetch();
                    break;
                case self::METHOD_EXEC:
                    $sql_type_str = strtolower(substr($sql, 0, 6));
                    if ($sql_type_str == 'insert' || $sql_type_str == 'update') {
                        // return last_insert_id when get_last_insert_id flag is true
                        // but if last_insert_id is empty, return affected_rows
                        if (empty($arg['get_last_insert_id'])) {
                            $result = $this->statement->rowCount();
                        } else {
                            $result = $this->pdo->lastInsertId();
                            // return affected_rows if last_insert_id is empty
                            if (empty($result)) {
                                $result = $this->statement->rowCount();
                            }
                        }
                    } else {
                        $result = 1;
                    }
                    break;
            }
            $this->statement->closeCursor();
        } catch(Exception $e) {
            throw new DbException(sprintf('db error: %s, sql: %s', $e->getMessage(), $sql),
                ExceptionCode::DB_FETCH_ERROR);
        }
        $this->close();
        return $result;
    }
    /**
     * begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    /**
     * commit
     */
    public function commit() {
        return $this->pdo->commit();
    }
    /**
     * rollback
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }
    /**
     * get connect string for pdo
     * @param string $host
     * @param string $dbname
     * @param int $port
     * @return string
     */
    private function getConnectString($host, $dbname, $port, $socket = null) {
        $socket = $socket === null ? ini_get('mysql.default_socket') : $socket;
        $connect_string = '';
        if($socket) {
            $connect_string = 'mysql:unix_socket=' . $socket . ';';
        } else {
            $connect_string = "mysql:host={$host};port={$port};";
        }
        $connect_string .= "dbname={$dbname};charset=utf8";
        return $connect_string;
    }

    /**
     * get connection
     * @throws DbException
     */
    public function getConnection($config) {
        if (!extension_loaded('pdo_mysql')) {
            return null;
        }
        $mysql_config = $this->filterMysqlConfig($config);
        $resource_pool = ResourcePool::getInstance();
        $resource_key = $resource_pool->getResourceKey($mysql_config);
        $pdo = $resource_pool->getResource(self::RESOURCE_TYPE, $resource_key);
        if($pdo) {
            return $pdo;
        }
        $connect_string = $this->getConnectString($mysql_config['host'], $mysql_config['dbname'],
            $mysql_config['port'], $mysql_config['socket']);
        __add_info(
            'DbDriverMysql#connect_string: ' . $connect_string,
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        try {
            $pdo = new PDO($connect_string, $mysql_config['dbuser'], $mysql_config['dbpass'],
                array(PDO::ATTR_AUTOCOMMIT => 1, PDO::ATTR_PERSISTENT => true));
        } catch (PDOException $e) {
            __add_info(
                'DbDriverMysql#pdo error: ' . $e->getMessage(),
                InfoCollector::TYPE_EXCEPTION,
                InfoCollector::LEVEL_DEBUG
            );
            throw new DbException('db error: ' . $e->getMessage(), ExceptionCode::DB_ACCESS_OBJ_ERROR);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE,  PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $resource_pool->registerResource(self::RESOURCE_TYPE, $resource_key, $pdo);
        return $pdo;
    }
    /**
     * filter mysql config
     * @param array $config
     * @throws DbException
     */
    private function filterMysqlConfig($config) {

        if(empty($config) || empty($config['host']) || empty($config['dbname'])) {
            throw new DbException('not enough configuration information', ExceptionCode::DB_CONFIG_NOT_EXIST);
        }
        $mysql_config = [];
        $mysql_config['host'] = empty($config['host']) ? 'localhost' : $config['host'];
        $mysql_config['port'] = empty($config['port']) ? 3306 : $config['port'];
        $mysql_config['socket'] = empty($config['socket']) ? null : $config['socket'];
        $mysql_config['dbname'] = empty($config['dbname']) ? '' : $config['dbname'];
        $mysql_config['dbuser'] = empty($config['dbuser']) ? '' : $config['dbuser'];
        $mysql_config['dbpass'] = empty($config['dbpass']) ? '' : $config['dbpass'];
        return $mysql_config;
    }
}
