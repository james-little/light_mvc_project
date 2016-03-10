<?php
namespace socket;

use Validator,
    resource\ResourcePool,
    resource\db\Db,
    resource\cache\Cache,
    socket\SocketServer;

/**
 * Socket Server For Resource Pool
 * =======================================================
 * This class overrides the onDataReceived() function of the socket server.
 * When server receives data(user_id) from socket client, server will
 * send all request(s) available of this user by using the AdRewardQueueProcessor
 * class.
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package socket
 * @version 1.0
 **/
class ResourcePoolSocketServer extends SocketServer {

    protected $_pool;
    protected $_resource_pool;
    protected $_max_resource_limit;
    protected $_resource_config;

    /**
     *
     * @param string $address
     * @param int $port
     * @param int $max_clients
     */
    public function __construct($address, $port, $max_clients) {
        parent::__construct($address, $port, $max_clients);
        $this->_pool = array();
        $this->_resource_pool = ResourcePool::getInstance();
    }

    /**
     * set resource config
     * @param string $category
     * @param array $config
     */
    public function setResourceConfig($category, $config) {
        $this->_resource_config[$category] = $config;
    }
    /**
     * get resource config
     * @param string $category
     * @return array
     */
    public function getResourceConfig($category) {
        return empty($this->_resource_config[$category]) ? array() : $this->_resource_config[$category];
    }

    public function setMaxResourceLimit($max_resource_limit) {
        if (is_numeric($max_resource_limit))
            $this->_max_resource_limit = $max_resource_limit;
    }

    /**
     * get db configuration
     * @param string $table_name
     */
    protected function getDbConfig($table_name) {
        if (!isset($this->_resource_config['db']['tables'][$table_name])) {
            return false;
        }
        $db_alias = $this->_resource_config['db']['tables'][$table_name];
        if (!isset($this->_resource_config['db']['hosts'][$db_alias])) {
            return false;
        }
        $this->_resource_config['db']['hosts'][$db_alias]['is_use_socket'] = false;
        return $this->_resource_config['db']['hosts'][$db_alias];
    }
    /**
     * get cache configuration
     * @param string $type
     */
    protected function getCacheConfig($type) {
        if (!isset($this->_resource_config['cache'][$type])) {
            return false;
        }
        return $this->_resource_config['cache'][$type]['hosts'];
    }
    /**
     * invoke when client data receives
     */
    protected function onDataReceived(&$socket, $data) {
        parent::onDataReceived($socket, $data);
        $result = $this->_process($data);
        if (is_array($result)) $result = json_encode($result);
        $this->_send($socket, $result);
    }
    /**
     * process received data
     * @param string $data
     *
     */
    protected function _process($data) {
        if (!preg_match('#([-a-zA-Z]+)://(.+)$#', $data, $tmp)) {
            return \ErrorCode::ERROR_SOCKET_INVALID_COMMAND;
        }
        $command = $tmp[1];
        $query = $tmp[2];
        parse_str(urldecode($query), $params);
        if (empty($params)) {
            return \ErrorCode::ERROR_SOCKET_COMMAND_QUERY_PARSE_ERROR;
        }
        unset($query);
        switch ($command) {
            case 'db':
                return $this->getDataFromDb($params);
            case 'cache':
                break;
            default:
                return \ErrorCode::ERROR_SOCKET_COMMAND_NOT_SUPPORT;
        }
    }
    /**
     * get data from database
     * @param array $params
     * @return mixed
     */
    protected function getDataFromDb($params) {

        $validate_result_list = Validator::getInstance()->validate(array(
            'type' => array('not empty;in list [mysql]', Validator::DATA_TYPE_STRING),
            'method_name' => array('not empty', Validator::DATA_TYPE_STRING),
            'sql' => array('not empty', Validator::DATA_TYPE_STRING)
        ), $params);
        foreach ($validate_result_list as $variable_name => $validate_result) {
            if (!$validate_result) {
                return \ErrorCode::ERROR_SOCKET_COMMAND_INVALID_PARAMS;
            }
        }
        $sql = urldecode($params['sql']);
        if(!preg_match('/(from|update|into) *`?([^`]+)`?/i', $sql, $tmp)) {
            return \ErrorCode::ERROR_SOCKET_COMMAND_CONFIG_NOT_EXIST;
        }
        $table_name = $tmp[2];
        $db_config = $this->getDbConfig($table_name);
        if (empty($db_config)) {
            return \ErrorCode::ERROR_SOCKET_COMMAND_CONFIG_NOT_EXIST;
        }
//         $connection_resource_list = $this->getResource('db', $params['type'], $db_config);
//         if (!is_array($connection_resource_list)) {
//             return \ErrorCode::ERROR_SOCKET_COMMAND_CONNECTION_NOT_EXIST;
//         }
//         // key of the connection
//         $key = $connection_resource_list[0];
//         $connection = $connection_resource_list[1];
//         unset($connection_resource_list);
        $command_exec = Db::getInstance()->applyConfig($db_config)->getCommand();
        $method_name = $params['method_name'];
        if (!method_exists($command_exec, $method_name)) {
            return \ErrorCode::ERROR_SOCKET_COMMAND_METHOD_NOT_EXIST;
        }
        $params['param'] = empty($params['param']) ? array() : $params['param'];
        $result = $command_exec->$method_name($sql, $params['param']);
//         $pool_key = $this->getPoolKey('db', $params['type']);
//         $this->_pool[$pool_key][$key][0] = 0;
        return $result;
    }


    protected function putResource($category, $type, $config) {

        $connection = null;
        switch ($category) {
            case 'db':
                $connection = Db::getInstance()->getDriver($type)->getConnection($config);
                break;
            case 'cache':
                $connection = Cache::getInstance()->bindCache($type, $config);
                break;
            default:
                return \ErrorCode::ERROR_SOCKET_COMMAND_NOT_SUPPORT;
        }
        $pool_key = $this->getPoolKey($category, $type);
        $this->_pool[$pool_key][] = array(0, $connection);
    }
    /**
     *
     * @param unknown_type $type
     */
    protected function getResource($category, $type, $config) {

        $pool_key = $this->getPoolKey($category, $type);
        if (empty($this->_pool[$pool_key])) {
            $this->putResource($category, $type, $config);
        }
        $count = $this->_pool[$pool_key];
        reset($this->_pool[$pool_key]);
        $max_loop = 10;
        while (true && $max_loop) {
            $key = key($this->_pool[$pool_key]);
            // check is locked
            if ($this->_pool[$pool_key][$key][0] == '0') {
                $this->_pool[$pool_key][$key][0] = 1;
                return array($key, $this->_pool[$pool_key][$key][1]);
            }
            if (next($this->_pool[$pool_key]) === false) {
                if ($count + 1 <= $this->_max_resource_limit) {
                    $this->putResource($category, $type, $config);
                } else {
                    sleep(1);
                }
                continue ;
            }
            $max_loop --;
        }
        return false;
    }

    private function getPoolKey($category, $type) {
        return $category . '_' . $type;
    }
    /**
     * make new log file every month
     */
    public function log($message, $soketError = false) {

        if ($this->is_debug) {
            $this->_log_file = $this->getLogFile();
        }
        parent::log($message, $soketError);
    }
    /**
     * Send data to a client
     * @param resource $client
     * @param string $data
     * @return bool
     */
    public function send(&$client, $data) {
        return $this->_send($client, $data);
    }
    /**
     * get log file
     * @return string log file
     */
    private function getLogFile() {
        $log_file = make_file('/tmp/mvc/resource_pool');
        if (!preg_match('#^/#', $log_file)) {
            $log_file = '/tmp/' . RUNTIME_ENVI . "_resource_pool_{$log_file}";
        }
        return $log_file;
    }

}