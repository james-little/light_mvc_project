<?php
use utility\File,
    log\writer\LogWriterStream;

/**
 * debugger
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class Debugger {

    protected $_log;
    protected $_log_dir = '/tmp/mvc/Debug';
    protected static $_instance;

    /**
     * get instance of exception handler
     */
    static public function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * __construct
     */
    protected function __construct() {
        $this->_log = ClassLoader::loadClass('\log\Log');
    }
    /**
     * debug
     * @param string $message
     */
    public function debug($message) {
        if (!AppRuntimeContext::getInstance()->getIsDebugMode()) {
            return ;
        }
        $this->writeLog($message);
    }
    /**
     * write log
     * @param string $message
     */
    private function writeLog($message) {
        if (empty($message)) {
            return ;
        }
        $file_name = make_file($this->_log_dir, 'daily');
        $this->_log->setWriter(new LogWriterStream($file_name));
        $this->_log->log($this->makeMessage($message));
    }
    /**
     * make debug message
     * @param string $message
     */
    private function makeMessage($message) {
        return sprintf("[%s]|%f|%s\n", date('Y-m-d H:i:s'), Monitor::getMemoryPeak(true), $message);
    }
}
