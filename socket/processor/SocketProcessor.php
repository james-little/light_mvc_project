<?php
namespace socket\processor;

use exception\SocketException,
    socket\processor\helper\SocketProcessorHelper;


/**
 * Socket Data Processor for Socket server
 * =======================================================
 * parse server data received from socket client and process them
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package socket\processor
 * @version 1.0
 **/
class SocketProcessor  {

    protected $helper_list;
    protected $_socket_server;

    /**
     * add helper to helper list
     * @param string $class_name
     * @return bool
     */
    public function addHelper($class_name) {

        $helper = \ClassLoader::loadClass($class_name);
        if (!$helper instanceof SocketProcessorHelper) {
            return false;
        }
        $this->helper_list[$class_name] = $helper;
        return true;
    }

    /**
     * get helper
     * @param string $class_name
     * @return bool | SocketProcessorHelper
     */
    public function getHelper($class_name) {
        return isset($this->helper_list[$class_name]) ? $this->helper_list[$class_name] : false;
    }

    /**
     * remove helper from helper list
     * @param string $class_name
     * @return bool
     */
    public function removeHelper($class_name) {
        if (isset($this->helper_list[$class_name])) {
            unset($this->helper_list[$class_name]);
            return true;
        }
        return false;
    }

    /**
     * process socket data by using helper
     * @param array | string $data
     */
    public function process($data, $params = null) {

        if (empty($this->helper_list)) {
            return ;
        }
        $params['socket_server'] = $this->_socket_server;
        foreach ($this->helper_list as $helper) {
            $helper->process($data, $params);
        }
    }
    /**
     * bind
     * @param SocketServer $server
     */
    public function bind($server) {
        $this->_socket_server = $server;
    }

    /**
     * __destruct
     */
    public function __destruct() {
        $this->helper_list = null;
    }
}