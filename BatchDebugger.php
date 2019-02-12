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
 */

namespace lightmvc;

use lightmvc\log\writer\LogWriterStream;
use lightmvc\OS;
use lightmvc\Application;

class BatchDebugger extends Debugger
{
    /**
     * __construct
     */
    protected function __construct()
    {
        parent::__construct();
        $this->_log_dir = Application::getLogBaseDir() . '/batch/debug';
    }
    /**
     * get tmp file name
     */
    protected function getTmpFileName($file_name)
    {

        $tmp = TMP_DIR . DS . Application::getProjectName() . '_' . APPLICATION_ENVI;
        if (defined('SCRIPT_NAME') && SCRIPT_NAME) {
            $tmp .= '_' . SCRIPT_NAME . '_';
        }
        return $tmp . 'batchdebug_' . $file_name;
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
        $file_name = make_file($this->_log_dir, $this->_mode, false);
        if ((OS::getCurrentOS() == OS::WINDOWS &&
            !preg_match('#^[A-Z]:#i', $file_name))
            ||
            (OS::getCurrentOS() == OS::LINUX &&
                substr($file_name, 0, 1) != '/')
        ) {
            $file_name = $this->getTmpFileName($file_name);
        }
        $new_file_name = '';
        if (defined('SCRIPT_NAME') && SCRIPT_NAME) {
            $pos           = strrpos($file_name, '/');
            $new_file_name = substr($file_name, 0, $pos) . '/' . SCRIPT_NAME . '_' . substr($file_name, $pos + 1);
        }
        $this->_log->setWriter(new LogWriterStream($new_file_name));
        $this->_log->log($this->makeMessage($message, $level));
    }
}
