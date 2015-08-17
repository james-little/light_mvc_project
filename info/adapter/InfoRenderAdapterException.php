<?php

namespace info\adapter;

use info\adapter\AbstractInfoRenderAdapter,
    Application,
    info\InfoCollector,
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
    protected $_log_dir;
    protected $_enabled;
    protected $_mode;

    /**
     * __construct
     */
    public function __construct() {
        $exception_config = Application::getConfigByKey('application', 'exception_log');
        $this->_log_dir = Application::getLogBaseDir() . '/exception';
        $this->_enabled = $exception_config['enabled'];
        $this->_mode = $exception_config['mode'];
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
                $date = date('Y/m/d H:i:s', $message_value_list['timestamp']);
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
        $message = '[' . date('Y/m/d H:i:s') . ']' . $message . "\n";
        $file_name = make_file($this->_log_dir, $this->_mode);
        if (substr($file_name, 0, 1) != '/') {
            $file_name = '/tmp/' . Application::getProjectName() . '_' . APPLICATION_ENVI . "_exception_{$file_name}";
        }
        $this->_log->setWriter(new LogWriterStream($file_name));
        $this->_log->log($message);
    }

}
