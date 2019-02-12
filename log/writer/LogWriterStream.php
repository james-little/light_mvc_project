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
 * log writer stream
 * ===================================================
 * write log file with file stream
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package log\writer
 * @version 1.0
 **/
namespace lightmvc\log\writer;

use lightmvc\exception\ExceptionCode;
use lightmvc\exception\IOException;
use lightmvc\log\writer\LogWriterInterface;

class LogWriterStream implements LogWriterInterface
{

    protected $_stream;

    /**
     * Class Constructor
     * @param  streamOrUrl     Stream or URL to open as a stream
     * @param  mode            Mode, only applicable if a URL is given
     */
    public function __construct($streamOrUrl, $mode = 'a')
    {
        if (is_resource($streamOrUrl)) {
            if (get_resource_type($streamOrUrl) != 'stream') {
                throw new IOException(
                    'Resource is not a stream',
                    ExceptionCode::IO_RESOURCE_NOT_STREAM
                );
            }
            if ($mode != 'a') {
                throw new IOException(
                    'Mode cannot be changed on existing streams',
                    ExceptionCode::IO_FILE_MODE_ERROR
                );
            }
            $this->_stream = $streamOrUrl;
        } else {
            $this->_stream = @fopen($streamOrUrl, $mode, false);
        }
    }
    /**
     * blocking write
     * @param string $message
     * @param string $level
     * @return boolean
     */
    public function write($message, $level = null)
    {
        if (!$this->_stream) {
            return false;
        }
        $start_time = microtime(true);
        do {
            $is_can_write = flock($this->_stream, LOCK_EX);
            if ($is_can_write) {
                @fwrite($this->_stream, $message);
                flock($this->_stream, LOCK_UN);
                return true;
            }
            usleep(1000);
        } while (microtime(true) - $start_time < 1);
        return false;
    }

    /**
     * close
     */
    public function close()
    {
        if (!$this->_stream) {
            return;
        }

        @fclose($this->_stream);
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->close();
        $this->_stream = null;
    }
    /**
     * __clone
     */
    public function __clone()
    {
        $this->close();
        $this->_stream = null;
    }
}
