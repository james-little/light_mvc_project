<?php
namespace resource\db\driver;

use resource\db\driver\DbDriverInterface,
    exception\DbException,
    info\InfoCollector,
    exception\ExceptionCode,
    \PDO,
    \PDOStatement;

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

    private $pdo;
    private $statement;

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
        return $this->query('queryAll', $sql, $param, $arg);
    }
    /**
     * get the first row of the result set
     * when query_cache is needed,  then $isUseCache must be set to true
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
     * return affected rows
     */
    public function exec($sql, $param = array(), $is_get_last_insert_id = false) {
        $arg = array();
        $arg['get_last_insert_id'] = (bool)$is_get_last_insert_id;
        return $this->query('exec', $sql, $param, $arg);
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
                __message('connection type not match. needed: PDO, given: %s', array(get_class($connection))),
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
        $map = array(
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'string' => PDO::PARAM_STR,
            'NULL' => PDO::PARAM_NULL,
        );
        return isset($map[$type]) ? $map[$type] : PDO::PARAM_STR;
    }
    /**
     * bind params to pdo statement
     * @param string $name
     * @param string $value
     * @param string $data_type
     * @param int $length
     */
    private function bindParam($name, &$value, $data_type = null, $length = null) {

        if($data_type === null) {
            $data_type = $this->getPdoType(gettype($value));
        }
        if($length === null) {
            $this->statement->bindParam($name, $value, $data_type);
        }else{
            $this->statement->bindParam($name, $value, $data_type, $length);
        }
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
     * @return mixed the method execution result
     */
    private function query($method, $sql, $param = array(), $arg = array()) {

        if (!$this->pdo instanceof \PDO) {
            throw new DbException(__message('pdo not set'), ExceptionCode::DB_ACCESS_OBJ_ERROR);
        }
        if (!$this->validateMethod($method)) {
            return false;
        }
        $result = null;
        if (empty($param)) {
            $param = array();
        }
        try {
            $this->statement = $this->pdo->prepare($sql);
            if($method != 'exec') {
                if (isset($arg['fetch_associative'])) {
                    $this->statement->setFetchMode(PDO::FETCH_ASSOC);
                }else{
                    if ($method != 'queryColumn') {
                        $this->statement->setFetchMode(PDO::FETCH_NUM);
                    }
                }
            }
            if($param !== array()) {
                $keys = array_keys($param);
                foreach($keys as $key) {
                    $this->bindParam($key, $param[$key]);
                }
            }
            $is_success = $this->statement->execute();
            if (!$is_success) {
                $last_error_code = $this->statement->errorCode();
                $last_error_info = $this->statement->errorInfo();
                throw new DbException(
                    __message('pdo execute error[ %s ]: %s', array($last_error_code, $last_error_info['2'])),
                    ExceptionCode::DB_EXECUTE_ERROR
                );
            }
            switch ($method) {
                case 'queryColumn':
                    $column_index = isset($arg['column_index']) ? $arg['column_index'] : 0;
                    $result = $this->statement->fetchAll(PDO::FETCH_COLUMN, $column_index);
                    break;
                case 'queryAll':
                    $result = $this->statement->fetchAll();
                    break;
                case 'queryRow':
                    $result = $this->statement->fetch();
                    break;
                case 'exec':
                    if (preg_match('#^insert#i', $sql)) {
                        $result = $this->pdo->lastInsertId();
                    } elseif (preg_match('#^update#i', $sql)) {
                        if (!empty($arg['get_last_insert_id'])) {
                            $result = $this->pdo->lastInsertId();
                        } else {
                            $result = $this->statement->rowCount();
                        }
                    } else {
                        $result = $is_success;
                    }
                    break;
            }
            $this->statement->closeCursor();
        }catch(\PDOException $e) {
            throw new DbException(__message('db error: %s', array($e->getMessage())));
        }
        $this->close();
        return $result;
    }
    /**
     * validate if method is available
     * @param string $method
     */
    private function validateMethod($method) {
        $method_list = array('queryColumn' => 0, 'queryAll' => 0, 'queryRow' => 0, 'exec' => 0);
        if (!isset($method_list[$method])) {
            return false;
        }
        return true;
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
        $connect_string = 'mysql:unix_socket=' . $socket. ';';
        $connect_string .= "dbname={$dbname};charset=utf8;port={$port};host={$host}";
        return $connect_string;
    }

    /**
     * get connection
     * @throws DbException
     */
    public function getConnection($config) {
        if (empty($config['host']) || empty($config['dbname'])) {
            throw new DbException(__message('not enough configuration information'));
        }
        $config['port'] = empty($config['port']) ? 3306 : $config['port'];
        $socket = empty($config['socket']) ? null : $config['socket'];
        $connect_string = $this->getConnectString($config['host'], $config['dbname'], $config['port'], $socket);
        __add_info(
            __message('DbDriverMysql#connect_string: %s', array($connect_string)),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        try {
            $connection = new PDO($connect_string, $config['dbuser'], $config['dbpass'],
                array(PDO::ATTR_AUTOCOMMIT => 0, PDO::ATTR_PERSISTENT => true));
            $connection->setAttribute(PDO::ATTR_ERRMODE,  PDO::ERRMODE_EXCEPTION);
            $connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        } catch (\PDOException $e) {
            __add_info(
                __message('DbDriverMysql#connection error: %s', array($e->getMessage())),
                InfoCollector::TYPE_EXCEPTION,
                InfoCollector::LEVEL_DEBUG
            );
            throw new DbException(__message('db error: %s', array($e->getMessage())));
        }
        return $connection;
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

}
