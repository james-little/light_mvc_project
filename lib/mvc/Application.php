<?php

use \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator,
    auth\AuthInterface,
    info\InfoCollector,
    info\InfoRender,
    exception\AppException,
    exception\ExceptionCode;

/**
 * Application
 * =======================================================
 * Implementation of application
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
abstract class Application {

    protected static $_instance;
    protected $_config;
    protected $_default_encode = 'UTF-8';
    protected $_auth;

    /**
     * constructor
     */
    protected function __construct(){

        if (!defined('APPLICATION_DIR')) {
            echo __message('APPLICATION_DIR not defined!');
            exit(0);
        }
        // bootstrap
        require_once APPLICATION_DIR . 'protected' . DS . 'bootstrap.php';
        // include framkework functions
        self::includeFrameworkFunctions();
        if (!class_exists('AppRuntimeContext')) {
            // AppRuntimeContext is must
            throw new AppException(__message('AppRuntimeContext not found under folder protected'), ExceptionCode::BUSINESS_LACK_OF_CLASS);
        }
        // set APPLICATION_ENVI
        AppRuntimeContext::getInstance()->getAppEnvi();
        // set is enable information collector
        AppRuntimeContext::getInstance()->setIsInfoCollectorEnabled();
    }
    /**
     * include framework functions
     */
    static public function includeFrameworkFunctions() {

        $expanded_functions_dir = __DIR__ . DIRECTORY_SEPARATOR . 'functions';
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($expanded_functions_dir)));
        foreach($objects as $entry => $object) {
            if(!preg_match('#\.php$#', $entry)) {
                continue;
            }
            $entry = str_replace('\\', '/', $entry);
            require_once $entry;
        }
        unset($objects);
    }
    /**
     * singleton
     * @return Application
     */
    public static function getInstance(){
        if(!static::$_instance){
            static::$_instance = new static();
        }
        return static::$_instance;
    }
    /**
     * set resource config
     * @param string $key
     * @param array $config
     */
    public function setConfig($key, $config) {
        $this->_config[$key] = $config;
    }
    /**
     * remove application config
     * @param string $key
     */
    public function removeConfig($key) {
        if (array_key_exists($key, $this->_config)) {
            unset($this->_config[$key]);
        }
    }
    /**
     * set auth callback array
     * @param array $callback_array
     *     . auth => 'call_auth_method' / auth => array($obj, $call_auth_method)
     *     . success => 'call_auth_method' / auth => array($obj, $call_auth_method)
     *     . failed => 'call_auth_method' / auth => array($obj, $call_auth_method)
     */
    public function setAuth(array $callback_array) {
        if (!array_key_exists('auth', $callback_array)) {
            throw new AppException(__message('auth callback not defined in auth config array'), ExceptionCode::APPLICATION_AUTH_CALLBACK_AUTH);
        }
        $this->_auth = $callback_array;
    }
    /**
     * run application
     * @throws AppException
     */
    public function run() {

        if (!defined('APPLICATION_ENVI')) {
            AppRuntimeContext::getInstance()->getAppEnvi();
        }
        if (!defined('APPLICATION_CONFIG_DIR')) {
            throw new AppException(__message('APPLICATION_CONFIG_DIR not defined'), ExceptionCode::CONFIG_CONST_NOT_DEFINED);
        }
        if (AppRuntimeContext::getInstance()->getAppRunmode() == AppRuntimeContext::MODE_CLI) {
            global $argv;
            $url = empty($argv[1]) ? null : $argv[1];
        } else {
            $url = current_url();
        }
        if (empty($url)) {
            throw new AppException(__message('url empty'), ExceptionCode::BUSINESS_URL_EMPTY);
        }
        if (!validate_url($url)) {
            throw new AppException(__message('invalid url: %s', array($url)), ExceptionCode::BUSINESS_URL_INVALID);
        }
        if ($this->_auth) {
            $auth = new Auth($url, $this->_auth);
            $auth->auth();
        }
        $url_array = parse_url($url);
        if (empty($url_array)) {
            throw new AppException(__message('parse url error'), ExceptionCode::BUSINESS_URL_PARSE_ERROR);
        }
        $path = empty($url_array['path']) ? '' : $url_array['path'];
        $dispatcher = ClassLoader::loadClass('\core\Dispatcher');
        $dispatcher->dispatch($path);
    }
}
