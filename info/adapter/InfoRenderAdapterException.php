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
 * InfoRenderAdapterException
 * =======================================================
 * handle exception messages
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package info\adapter
 * @version 1.0
 */
namespace lightmvc\info\adapter;

use lightmvc\Application;
use lightmvc\ClassLoader;
use lightmvc\info\adapter\AbstractInfoRenderAdapter;
use lightmvc\info\InfoCollector;
use lightmvc\log\writer\LogWriterStream;
use lightmvc\OS;

class InfoRenderAdapterException extends AbstractInfoRenderAdapter
{

    protected $_log;
    protected $_log_dir;
    protected $_enabled;
    protected $_mode;

    /**
     * __construct
     */
    public function __construct()
    {
        $exception_config = Application::getConfigByKey('application', 'exception_log');
        $this->_log_dir   = Application::getLogBaseDir() . '/exception';
        $this->_enabled   = $exception_config['enabled'];
        $this->_mode      = $exception_config['mode'];
        $this->_log       = ClassLoader::loadClass('\lightmvc\log\Log');
    }

    /**
     * render
     */
    public function render(InfoCollector $info_collector)
    {
        $message_list = $info_collector->getMessages(InfoCollector::TYPE_EXCEPTION);
        if (!empty($message_list)) {
            // sort message value list by timestamp(low -> high)
            $message_list = $this->sortMessageListByTimestamp($message_list);
            foreach ($message_list as $message_value_list) {
                $date    = date('Y/m/d H:i:s', $message_value_list['timestamp']);
                $message = "time:{$date}#message:{$message_value_list['message']}";
                $this->writeLog($message);
            }
        }
    }

    /**
     * write log
     * @param string $message
     */
    private function writeLog($message)
    {
        if ($message == '') {
            return;
        }
        $message   = '[' . date('Y/m/d H:i:s') . ']' . $message . "\n";
        $file_name = make_file($this->_log_dir, $this->_mode);
        if ((OS::getCurrentOS() == OS::WINDOWS &&
            !preg_match('#^[A-Z]:#i', $file_name))
            ||
            (OS::getCurrentOS() == OS::LINUX &&
                substr($file_name, 0, 1) != '/')
        ) {
            $file_name = TMP_DIR . '/' . Application::getProjectName() . '_' . APPLICATION_ENVI . "_exception_{$file_name}";
        }
        $this->_log->setWriter(new LogWriterStream($file_name));
        $this->_log->log($message);
    }
}
