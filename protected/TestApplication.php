<?php

use info\InfoCollector,
    exception\ExceptionCode;

/**
 * TestApplication
 * @author koketsu <jameslittle.private@gmail.com>
 */
class TestApplication extends Application {

    /**
     * constructor
     */
    protected function __construct(){
        parent::__construct();
        // config directory
        define('APPLICATION_CONFIG_DIR', APPLICATION_DIR . 'protected' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR);
        // common application_config
        $application_config = APPLICATION_CONFIG_DIR . 'common/application.php';
        if (!file_exists($application_config)) {
            throw new AppException('application config not found in %s', array($application_config), ExceptionCode::CONFIG_FILE_NOT_EXIST);
        }
        $application_config = require_once $application_config;
        // put config settings intp application context
        AppRuntimeContext::getInstance()->setData('application_config', $application_config);
        $this->_setEncodingConfig($application_config);
        // timezone
        date_default_timezone_set($application_config['time_zone']);
        // Localization
        if (!empty($application_config['locale']) && $application_config['locale']['lang'] != 'english') {
            define('APPLICATION_LANG', $application_config['locale']['lang']);
        }
        if (defined('APPLICATION_LANG')) {
            putenv('LANG=' . APPLICATION_LANG);
            setlocale(LC_ALL, $application_config['locale']['locale']);
            bindtextdomain('default', APPLICATION_DIR . 'locale');
            textdomain('default');
        }
    }
    /**
     * set encoding config
     * @param array $config
     */
    protected function _setEncodingConfig($config) {

        if (isset($config['encode'])) {
            if (isset($config['encode']['default'])) {
                $this->_default_encode = $config['encode']['default'];
            }
        }
        $input_encode = $output_encode = $this->_default_encode;
        if (isset($config['encode'])) {
            if (isset($config['encode']['output'])) {
                $output_encode = $config['encode']['output'];
            }
        }
        // encoding settings
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        mb_http_output($output_encode);
    }
}
