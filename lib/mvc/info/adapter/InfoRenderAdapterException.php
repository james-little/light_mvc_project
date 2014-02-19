<?php

namespace info\adapter;

use info\adapter\AbstractInfoRenderAdapter,
    Monitor,
    info\InfoCollector,
    utility\File,
    ClassLoader,
    log\writer\LogWriterStream;

/**
 * InfoRenderAdapterException
 * =======================================================
 * handle exception messages
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @package info\adapter
 * @version 1.0
 */
class InfoRenderAdapterException extends AbstractInfoRenderAdapter {

    protected $_log;
    protected $_log_dir = '/tmp/mvc/Exception';

    /**
     * __construct
     */
    public function __construct() {
        $this->_log = ClassLoader::loadClass('\log\Log');
    }

    /**
     * render
     */
    public function render(InfoCollector $info_collector) {

        $message_list = $info_collector->getMessages(InfoCollector::TYPE_EXCEPTION);
        if (!empty($message_list)) {
            // sort message value list by timestamp(low -> high)
            $message_list = $this->sortMessageListByTimestamp($message_list);
            foreach ($message_list as $message_value_list) {
                $date = date('Y-m-d H:i:s', $message_value_list['timestamp']);
                $message = "time:{$date}#message:{$message_value_list['message']}";
                $this->writeLog($message);
            }
        }
    }

    /**
     * write log
     * @param string $message
     */
    private function writeLog($message) {

        if ($message == '') {
            return ;
        }
        $message = '[' . date('Y-m-d H:i:s') . ']' . $message . "\n";
        $file_name = make_file($this->_log_dir, 'daily');
        if (!preg_match('#^/#', $file_name)) {
            $file_name = '/tmp/' . APPLICATION_ENVI . "_exception_{$log_file}";
        }
        $this->_log->setWriter(new LogWriterStream($file_name));
        $this->_log->log($message);
    }

}
