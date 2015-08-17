<?php

use log\writer\LogWriterStream;

class BatchDebugger extends Debugger {

    /**
     * __construct
     */
    protected function __construct() {
        parent::__construct();
        $this->_log_dir = Application::getLogBaseDir() . '/batch/debug';
    }
    /**
     * get tmp file name
     */
    protected function getTmpFileName($file_name) {
        $tmp = '/tmp/' . Application::getProjectName() . '_' . APPLICATION_ENVI;
        if (defined('SCRIPT_NAME') && SCRIPT_NAME) {
            $tmp .= '_' . SCRIPT_NAME . '_';
        }
        return $tmp . 'batchdebug_' . $file_name;
    }
    /**
     * write log
     * @param string $message
     */
    protected function writeLog($message, $level) {
        if (empty($message)) {
            return ;
        }
        $file_name = make_file($this->_log_dir, $this->_mode, false);
        if (substr($file_name, 0, 1) != '/') {
            $file_name = $this->getTmpFileName();
        }
        if (defined('SCRIPT_NAME') && SCRIPT_NAME) {
            $pos = strrpos($file_name, '/');
            $new_file_name = substr($file_name, 0, $pos) . '/' . SCRIPT_NAME . '_' . substr($file_name, $pos + 1);
        }
        $this->_log->setWriter(new LogWriterStream($new_file_name));
        $this->_log->log($this->makeMessage($message, $level));
    }
}