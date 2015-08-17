<?php
namespace queue\adapter;

use exception\QueueException,
    exception\ExceptionCode,
    \Application,
    \File,
    log\writer\LogWriterStream;

/**
 * queue adapter class
 * =================================================
 * ueue adapter
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @version 1.0
 **/
abstract class QueueAdapter {

    protected $_log;
    protected $_log_dir;
    protected $is_log_enabled = false;
    protected $log_mode = 'daily';

    /**
     * log_mode:
     * MONTHLY  : one file per month
     * WEEKLY   : one file per week, one folder per month with all week files
     *            in that month
     * DAILY    : one file per day, one folder per month with all day files
     *            in that month
     * HOURLY   : one file per hour, one folder per month with all hour files
     *            in that month
     */
    const LOG_MODE_MONTHLY = 'monthly';
    const LOG_MODE_WEEKLY = 'weekly';
    const LOG_MODE_DAILY = 'daily';
    const LOG_MODE_HOURLY = 'hourly';

    /**
     * Configure queue by config
     * @param array $config
     *     . adapter         : string. adapter class name
     */
    public function applyConfig($config) {

        if (isset($config['log']['enabled'])) {
            $this->setIsLogEnabled($config['log']['enabled']);
        }
        if ($this->getIsLogEnabled()) {
            if (isset($config['log']['log_dir'])) {
                $this->setLogDir($config['log']['log_dir']);
            }
            if (isset($config['log']['mode'])) {
                $this->setLogMode($config['log']['mode']);
            }
            // set user customized log class
            if (!empty($config['log']['log_class'])) {
                $queue_logger = \ClassLoader::loadClass($config['log']['log_class']);
                $this->_log = $queue_logger;
            } else {
                $this->_log = \ClassLoader::loadClass('\log\Log');
            }
            $this->_log_dir = Application::getLogBaseDir() . '/queue';
        }
    }
    /**
     * log mode setter
     * @param string $log_mode
     */
    public function setLogMode($log_mode) {
        $this->log_mode = $log_mode;
    }
    /**
     * log mode getter
     * @return string $log_mode
     */
    public function getLogMode() {
        return $this->log_mode;
    }
    /**
     * log_dir setter
     * @param string $log_dir
     */
    public function setLogDir($log_dir) {
        $this->_log_dir = $log_dir;
    }
    /**
     * log_dir getter
     * @return string log_dir
     */
    public function getLogDir() {
        return $this->_log_dir;
    }
    /**
     * is_log_enabled setter
     * @param bool $is_log_enabled
     */
    public function setIsLogEnabled($is_log_enabled) {
        $this->is_log_enabled = $is_log_enabled;
    }
    /**
     * is_log_enabled getter
     * @return bool is_log_enabled
     */
    public function getIsLogEnabled() {
        return $this->is_log_enabled;
    }
    /**
     * get file path of the log file. create log folder if not exist
     */
    protected function getLogFilePath() {

        $file_type = '';
        switch ($this->log_mode) {
            case self::LOG_MODE_MONTHLY:
                $file_type = File::MKFILE_TYPE_MONTHLY;
                break;
            case self::LOG_MODE_WEEKLY:
                $file_type = File::MKFILE_TYPE_WEEKLY;
                break;
            case self::LOG_MODE_DAILY:
                $file_type = File::MKFILE_TYPE_DAILY;
                break;
            case self::LOG_MODE_HOURLY:
                $file_type = File::MKFILE_TYPE_HOURLY;
                break;
        }
        $log_file = File::makeFile($this->_log_dir, $file_type, true);
        if (substr($log_file, 0, 1) != '/') {
            $log_file = '/tmp/' . Application::getProjectName() . '_' . APPLICATION_ENVI . "_queue_{$file_name}";
        }
        return $log_file;
    }
    /**
     * write log to file in different log mode
     * @param string $message
     * @throws QueueException
     */
    public function log($message) {
        if (!$this->_log) {
            throw new QueueException('log was not set', ExceptionCode::QUEUE_LOG_NOT_SET);
        }
        if (!$this->_log->getWriter()) {
            $file_name = $this->getLogFilePath();
            @file_put_contents($file_name, '', FILE_APPEND);
            $this->_log->setWriter(new LogWriterStream($file_name));
        }
        return $this->_log->log($message);
    }
}