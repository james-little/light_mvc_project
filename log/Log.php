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
 * Log
 * =======================================================
 * log class
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package log
 * @version 1.0
 **/
namespace lightmvc\log;

use lightmvc\exception\IOException;
use lightmvc\log\writer\LogWriterInterface;
use ReflectionClass;

class Log
{

    const LEVEL_EMERG  = 0; // Emergency: system is unusable
    const LEVEL_ALERT  = 1; // Alert: action must be taken immediately
    const LEVEL_CRIT   = 2; // Critical: critical conditions
    const LEVEL_ERR    = 3; // Error: error conditions
    const LEVEL_WARN   = 4; // Warning: warning conditions
    const LEVEL_NOTICE = 5; // Notice: normal but significant condition
    const LEVEL_INFO   = 6; // Informational: informational messages
    const LEVEL_DEBUG  = 7; // Debug: debug messages

    protected $_writer;

    /*
     */
    public function __construct(LogWriterInterface $writer = null)
    {
        if ($writer) {
            $this->_writer = $writer;
        }
    }
    /*
     */
    public function __destruct()
    {
        if ($this->_writer) {
            $this->_writer->close();
        }

        $this->_writer = null;
    }
    /*
     */
    public function __clone()
    {
        $this->_writer = null;
    }
    /*
     */
    public function setWriter(LogWriterInterface $writer)
    {
        $this->_writer = $writer;
    }
    /*
     */
    public function getWriter()
    {
        return $this->_writer;
    }
    /**
     * log message
     * @param string $message
     * @param int $level
     * @throws IOException
     * @return boolean|mixed
     */
    public function log($message, $level = self::LEVEL_DEBUG)
    {
        if (!$this->_writer) {
            throw new IOException('No writer was set');
        }

        $refl             = new ReflectionClass('lightmvc\log\Log');
        $level_const_list = $refl->getConstants();

        $is_write = false;
        if (in_array($level, $level_const_list)) {
            $is_write = true;
        }
        if (!$is_write) {
            return false;
        }
        $this->onBeforeWriteLog();
        $is_success = $this->_writer->write($message, $level);
        $this->onAfterWriteLog($is_success);
        return $is_success;
    }
    /**
     * (non-PHPdoc)
     * @see log.LogInterface::onBeforeWriteLog()
     */
    public function onBeforeWriteLog()
    {
    }

    /**
     * (non-PHPdoc)
     * @see log.LogInterface::onAfterWriteLog()
     */
    public function onAfterWriteLog($is_success)
    {
    }
}
