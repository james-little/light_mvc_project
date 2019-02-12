<?php
/**
 *  Copyright 2016 Koketsu
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
 * debugger
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

use lightmvc\info\InfoCollector;
use lightmvc\log\writer\LogWriterStream;
use lightmvc\Application;
use lightmvc\ClassLoader;
use lightmvc\OS;
use lightmvc\Monitor;

class Debugger
{

    protected $_log;
    protected $_log_dir;
    protected $_enabled;
    protected $_mode;
    protected static $_instance;

    /**
     * __construct
     */
    protected function __construct()
    {
        $debug_config   = Application::getConfigByKey('application', 'debug_log');
        $this->_log_dir = Application::getLogBaseDir() . '/debug';
        $this->_enabled = $debug_config['enabled'];
        $this->_mode    = $debug_config['mode'];
        $this->_log     = ClassLoader::loadClass('\lightmvc\log\Log');
    }

    /**
     * get instance of exception handler
     */
    public static function getInstance()
    {
        if (self::$_instance !== null) {
            return self::$_instance;
        }
        self::$_instance = new static();
        return self::$_instance;
    }
    /**
     * debug
     * @param string $message
     */
    public function debug($message, $level)
    {
        if (!$this->_enabled) {
            return;
        }

        if (!defined('APPLICATION_IS_DEBUG') || !APPLICATION_IS_DEBUG) {
            return;
        }
        $this->writeLog($message, $level);
    }
    /**
     * write log
     * @param string $message
     */
    protected function writeLog($message, $level)
    {
        if (empty($message)) {
            return;
        }
        $file_name = make_file($this->_log_dir, $this->_mode);
        if ((OS::getCurrentOS() == OS::WINDOWS &&
            !preg_match('#^[A-Z]:#i', $file_name))
            ||
            (OS::getCurrentOS() == OS::LINUX &&
                substr($file_name, 0, 1) != '/')
        ) {
            $file_name = $this->getTmpFileName($file_name);
        }
        $this->_log->setWriter(new LogWriterStream($file_name));
        $this->_log->log($this->makeMessage($message, $level));
    }
    /**
     * get tmp file name
     */
    protected function getTmpFileName($file_name)
    {
        $tmp_dir = sys_get_temp_dir();
        if (empty($file_name)) {
            $file_name = date('YmdHis');
        }
        return TMP_DIR . DIRECTORY_SEPARATOR . Application::getProjectName() . '_' . APPLICATION_ENVI . "_debug_{$file_name}";
    }
    /**
     * make debug message
     * @param string $message
     * @param int $level
     * @return string
     */
    protected function makeMessage($message, $level)
    {
        return sprintf(
            "[%s][%s]|%f|%s\n",
            date('Y/m/d H:i:s'),
            $this->levelToString($level),
            Monitor::getMemoryPeak(true),
            $message
        );
    }
    /**
     * convert info level to string
     * @param int $level
     * @return string
     */
    protected function levelToString($level)
    {
        switch ($level) {
            case InfoCollector::LEVEL_EMERG:
                return 'EMERG';
            case InfoCollector::LEVEL_ALERT:
                return 'ALERT';
            case InfoCollector::LEVEL_CRIT:
                return 'CRIT';
            case InfoCollector::LEVEL_ERR:
                return 'ERR';
            case InfoCollector::LEVEL_WARN:
                return 'WARN';
            case InfoCollector::LEVEL_NOTICE:
                return 'NOTICE';
            case InfoCollector::LEVEL_INFO:
                return 'INFO';
            case InfoCollector::LEVEL_DEBUG:
                return 'DEBUG';
        }
        return '';
    }
}
