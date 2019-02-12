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
 * Socket Data Processor for Socket server
 * =======================================================
 * parse server data received from socket client and process them
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package socket\processor
 * @version 1.0
 **/
namespace lightmvc\socket\processor;

use lightmvc\socket\processor\helper\SocketProcessorHelper;
use lightmvc\ClassLoader;

class SocketProcessor
{

    protected $helper_list;
    protected $_socket_server;

    /**
     * add helper to helper list
     * @param string $class_name
     * @return bool
     */
    public function addHelper($class_name)
    {
        $helper = ClassLoader::loadClass($class_name);
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
    public function getHelper($class_name)
    {
        return isset($this->helper_list[$class_name]) ? $this->helper_list[$class_name] : false;
    }

    /**
     * remove helper from helper list
     * @param string $class_name
     * @return bool
     */
    public function removeHelper($class_name)
    {
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
    public function process($data, $params = null)
    {
        if (empty($this->helper_list)) {
            return;
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
    public function bind($server)
    {
        $this->_socket_server = $server;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->helper_list = null;
    }
}
