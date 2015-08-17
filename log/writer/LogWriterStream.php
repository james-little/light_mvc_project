<?php
namespace log\writer;

use log\writer\LogWriterInterface,
    exception\ExceptionCode,
    exception\IOException;

/**
 * log writer stream
 * ===================================================
 * write log file with file stream
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package log\writer
 * @version 1.0
 **/
class LogWriterStream implements LogWriterInterface {

    protected $_stream;

    /**
     * Class Constructor
     * @param  streamOrUrl     Stream or URL to open as a stream
     * @param  mode            Mode, only applicable if a URL is given
     */
    public function __construct($streamOrUrl, $mode = 'a') {

        if (is_resource($streamOrUrl)) {
            if (get_resource_type($streamOrUrl) != 'stream') {
                throw new IOException('Resource is not a stream',
                    ExceptionCode::IO_RESOURCE_NOT_STREAM);
            }
            if ($mode != 'a') {
                throw new IOException('Mode cannot be changed on existing streams',
                    ExceptionCode::IO_FILE_MODE_ERROR);
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
    public function write($message, $level = null) {

        if (!$this->_stream) return false;
        $start_time = microtime(true);
        do {
            $is_can_write = flock($this->_stream, LOCK_EX);
            if($is_can_write) {
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
    public function close() {
        if (!$this->_stream) return ;
        @fclose($this->_stream);
    }

    /**
     * __destruct
     */
    public function __destruct() {
        $this->close();
        $this->_stream = null;
    }
    /**
     * __clone
     */
    public function __clone() {
        $this->close();
        $this->_stream = null;
    }

}