<?php
use auth\AuthLogicInterface;
use context\RuntimeContext;
use core\Dispatcher;
use core\http\Request;
use core\http\Response;
use exception\AppException;
use exception\ExceptionCode;
use info\InfoCollector;

/**
 * Application
 * =======================================================
 * Implementation of application
 *
 * language:
 *     cookie -> request -> user_config -> application_config
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
abstract class Application {

    protected static $_instance;
    protected static $_config;
    protected $_auth_logic;
    protected static $input_encoding;
    protected static $output_encoding;
    private static $dispatcher;
    protected $_error_handler = '/common/AppError/handle';

    const LANG_EN = 1;
    const LANG_JP = 2;
    const LANG_CN = 3;

    /**
     * constructor
     */
    protected function __construct($cli_manual_envi = null) {

        self::$_config = [];
        self::init($cli_manual_envi);
        // shutdown handler
        register_shutdown_function([$this, 'beforeShutdown']);
    }
    /**
     * init
     * @throws AppException
     */
    public static function init($cli_manual_envi = null) {

        if (!defined('APPLICATION_DIR')) {
            throw new AppException('APPLICATION_DIR not defined!', ExceptionCode::APP_DIR_NOT_DEFINED);
        }
        $bootstrap = APPLICATION_DIR . 'protected' . DS . 'bootstrap.php';
        if (!is_file($bootstrap)) {
            throw new AppException('bootstrap.php not found!', ExceptionCode::APP_BOOTSTRAP_NOT_FOUND);
        }
        // bootstrap
        include $bootstrap;
        // include framkework functions
        self::includeFrameworkFunctions();
        if (!class_exists('AppRuntimeContext')) {
            // AppRuntimeContext is must
            throw new AppException(
                'AppRuntimeContext not found under folder protected',
                ExceptionCode::APP_RUNTIME_CONTEXT_NOT_EXIST
            );
        }
        // config directory
        define('APPLICATION_CONFIG_DIR', APPLICATION_DIR . 'protected' . DS . 'config' . DS);
        // common application_config
        $application_config = APPLICATION_CONFIG_DIR . 'common/application.php';
        if (!is_file($application_config)) {
            throw new AppException(
                'application config not found in ' . $application_config,
                ExceptionCode::APP_CONFIG_FILE_NOT_EXIST
            );
        }
        $application_config = require $application_config;
        self::setEncodingConfig($application_config['encode']);
        // fix encoding setting by user agent
        Useragent::getCarrier();
        // timezone
        date_default_timezone_set($application_config['time_zone']);
        // put config settings intp application context
        self::setConfig('application', $application_config);
        // crypt key
        define('APPLICATION_CRYPT_KEY', self::getConfigByKey('application', 'crypt_key', 'key'));
        define('APPLICATION_CRYPT_IV', self::getConfigByKey('application', 'crypt_key', 'iv'));
        // crypt key for javascript
        self::setJScriptCryptKey();
        // set session config
        Session::applyConfig(Application::getConfigByKey('application', 'session'));
        // set project log on/off
        define('APPLICATION_KPI_LOG', Application::getConfigByKey('application', 'project_log', 'enabled'));
        define('APPLICATION_COMPRESS_ENABLED', Application::getConfigByKey('application', 'enable_compressing'));
    }
    /**
     * include framework functions
     */
    public static function includeFrameworkFunctions() {

        $expanded_functions_dir = __DIR__ . DIRECTORY_SEPARATOR . 'functions';
        $objects                = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($expanded_functions_dir)));
        foreach ($objects as $entry => $object) {
            if (substr($entry, -4) != '.php') {
                continue;
            }
            $entry = str_replace('\\', '/', $entry);
            include $entry;
        }
    }
    /**
     * set application locale
     */
    public static function setApplicationLocale($lang = null) {

        if ($lang && !in_array($lang, [
            Application::LANG_EN, Application::LANG_JP, Application::LANG_CN,
        ])) {
            return;
        }
        if (!$lang) {
            $locale = self::getConfigByKey('application', 'locale');
            $lang   = self::getLangByLocale($locale);
        }
        if (!defined('APPLICATION_LANG')) {
            define('APPLICATION_LANG', $lang);
            self::setLocaleDomain($lang, APPLICATION_DIR . 'locale');
        }
        // set language info to cookie
        set_cookie_pro('lang', $lang, null, 3600 * 24 * 30);
    }
    /**
     * get language list
     * @return array
     */
    public static function getLangList() {
        return [
            self::LANG_EN => __message("English"),
            self::LANG_JP => __message("Japanese"),
            self::LANG_CN => __message("Chinese"),
        ];
    }
    /**
     * get locale name by language
     * @param  int $lang
     * @return string
     */
    public static function getLocaleByLang($lang) {
        switch ($lang) {
        case self::LANG_JP:
            return 'ja-JP';
        case self::LANG_CN:
            return 'zh-CN';
        }
        return 'en-US';
    }
    /**
     * get lang by locale
     * @param  string $locale
     * @return int
     */
    public static function getLangByLocale($locale) {
        switch ($locale) {
        case 'ja-JP':
            return self::LANG_JP;
        case 'zh-CN':
            return self::LANG_CN;
        }
        return self::LANG_EN;
    }
    /**
     * set locale
     * @param $lang
     * @return void
     */
    public static function setLocaleDomain($lang, $dir, $module = null) {

        if ($lang == self::LANG_EN) {
            return;
        }
        $locale = str_replace('-', '_', self::getLocaleByLang($lang));
        putenv('LANG=' . $locale);
        setlocale(LC_ALL, $locale);
        $domain = self::getProjectName();
        if ($module) {
            $domain .= "_{$module}";
        }
        bind_textdomain_codeset($domain, 'UTF-8');
        bindtextdomain($domain, $dir);
        textdomain($domain);
        __add_info(sprintf('locale set to %s. domain: %s, dir: %s', $locale, $domain, $dir),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
    }
    /**
     * read maintenance config
     */
    protected function getMaintenanceConfig() {

        $config = APPLICATION_CONFIG_DIR;
        $envi   = RuntimeContext::getInstance()->getAppEnvi();
        switch ($envi) {
        case RuntimeContext::ENVI_PRODUCTION:
            $config .= 'production/maintenance.php';
            break;
        case RuntimeContext::ENVI_STAGING:
            $config .= 'staging/maintenance.php';
            break;
        case RuntimeContext::ENVI_DEVELOP:
            $config .= 'develop/maintenance.php';
            break;
        }
        if (!is_file($config)) {
            return [];
        }
        $config = include $config;
        return $config;
    }
    /**
     * singleton
     * @return Application
     */
    public static function getInstance($cli_manual_envi = null) {

        if (static::$_instance !== null) {
            return static::$_instance;
        }
        static::$_instance = new static($cli_manual_envi);
        return static::$_instance;
    }
    /**
     * set resource config
     * @param string $key
     * @param array $config
     */
    public static function setConfig($category, $config) {

        if (empty($category)) {
            return;
        }
        static::$_config[$category] = $config;
    }
    /**
     * remove application config
     * @param string $key
     */
    public static function removeConfig($category, $key = null) {

        if (!array_key_exists($category, static::$_config)) {
            return;
        }
        if ($key && array_key_exists($key, static::$_config[$category])) {
            unset(static::$_config[$category]);
            return;
        }
        unset(static::$_config[$category]);
    }
    /**
     * get config
     */
    public static function getConfig() {
        return static::$_config;
    }
    /**
     * get config
     */
    public static function getConfigByKey($category, $key = null, $sec_key = null) {

        if (!array_key_exists($category, static::$_config)) {
            return null;
        }
        if ($key && !$sec_key && array_key_exists($key, static::$_config[$category])) {
            return static::$_config[$category][$key];
        }
        if ($key && $sec_key && array_key_exists($sec_key, static::$_config[$category][$key])) {
            return static::$_config[$category][$key][$sec_key];
        }
        return static::$_config[$category];
    }
    /**
     * get project name
     */
    public static function getProjectName() {
        return self::getConfigByKey('application', 'project_name');
    }
    /**
     * get project log base dir
     */
    public static function getLogBaseDir() {

        $log_base_dir = self::getConfigByKey('application', 'log_base_dir');
        $log_base_dir = str_replace('%project_name%', self::getProjectName(), $log_base_dir);
        return $log_base_dir;
    }
    /**
     * get input encoding
     */
    public static function getInputEncoding() {
        return static::$input_encoding;
    }
    /**
     * get output encoding
     */
    public static function getOutputEncoding() {
        return static::$output_encoding;
    }
    /**
     * set auth logic
     * @param AuthLogicInterface $auth_logic
     * @throws AppException
     */
    public function setAuth(AuthLogicInterface $auth_logic) {

        if (empty($auth_logic)) {
            throw new AppException('auth logic should not be empty', ExceptionCode::APP_AUTH_CALLBACK_AUTH);
        }
        $this->_auth_logic = $auth_logic;
    }
    /**
     * set encoding config
     * @param array $config
     */
    public static function setEncodingConfig($config) {

        $default_encode = 'UTF-8';
        if (isset($config['default'])) {
            $default_encode = $config['default'];
        }
        $input_encode = $output_encode = $default_encode;
        if (isset($config['output'])) {
            $output_encode = $config['output'];
        }
        if (isset($config['input'])) {
            $input_encode = $config['input'];
        }
        self::$input_encoding  = $input_encode;
        self::$output_encoding = $output_encode;
        // encoding settings
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        mb_http_output(String::convertEncodingName($output_encode));
    }
    /**
     * run application
     * @throws AppException
     */
    public function run() {

        if (!defined('APPLICATION_ENVI')) {
            $this->handleException(new AppException(
                'APPLICATION_ENVI not defined',
                ExceptionCode::APP_ENVI_NOT_DEFINED
            ));
            return;
        }
        if (!defined('APPLICATION_CONFIG_DIR')) {
            $this->handleException(new AppException(
                'APPLICATION_CONFIG_DIR not defined',
                ExceptionCode::APP_CONFIG_DIR_NOT_DEFINED
            ));
            return;
        }
        $maintenance_info = [];
        if ($this->isInMaintenance($maintenance_info)) {
            $this->handleException(new AppException(
                "application is in maintenance:[{$maintenance_info['start_time']},{$maintenance_info['end_time']}]",
                ExceptionCode::APP_IN_MAINTENANCE
            ));
            return;
        }
        $maintenance_info = null;
        $current_path     = Request::getCurrentUrlPath();
        try {
            if ($this->_auth_logic) {
                $auth = new Auth($this->_auth_logic, array('url_path' => $current_path));
                $auth->auth();
            }
            // loccalization
            self::setApplicationLocale();
            $dispatcher        = $this->getDispatcher();
            $response_contents = $dispatcher->dispatch($current_path);
        } catch (Exception $e) {
            $this->handleException($e);
            return;
        }
        $response = Response::getInstance();
        $response->setContents($response_contents);
        $response->send();
    }
    /**
     * handle exception (throw exception when error code is not defined)
     */
    protected function handleException($e) {

        RuntimeContext::getInstance()->setData('render_error_exception', $e);
        $dispatcher        = $this->getDispatcher();
        $response_contents = $dispatcher->dispatch($this->_error_handler);
        $response          = Response::getInstance();
        $response->setContents($response_contents);
        $response->send();
    }
    /**
     * get dispatcher
     * @return Dispatcher
     */
    private function getDispatcher() {

        if (self::$dispatcher !== null) {
            return self::$dispatcher;
        }
        self::$dispatcher = new Dispatcher();
        return self::$dispatcher;
    }
    /**
     * is in maintenance
     * @return bool
     */
    public function isInMaintenance(&$maintenance_info = null) {

        $config = $this->getMaintenanceConfig();
        if (empty($config)) {
            if ($maintenance_info) {
                $maintenance_info = [
                    'start_time' => 0,
                    'end_time'   => 0,
                ];
            }
            return false;
        }
        if (empty($config['start_time']) || empty($config['end_time'])) {
            if ($maintenance_info) {
                $maintenance_info = [
                    'start_time' => empty($config['start_time']) ? '' : strtotime($config['start_time']),
                    'end_time'   => empty($config['end_time']) ? '' : strtotime($config['end_time']),
                ];
            }
            return false;
        }
        $now_time = time();
        if ($now_time >= strtotime($config['start_time']) && $now_time <= strtotime($config['end_time'])) {
            if ($maintenance_info) {
                $maintenance_info = [
                    'start_time' => strtotime($config['start_time']),
                    'end_time'   => strtotime($config['end_time']),
                ];
            }
            return true;
        }
        if ($maintenance_info) {
            $maintenance_info = [
                'start_time' => strtotime($config['start_time']),
                'end_time'   => strtotime($config['end_time']),
            ];
        }
        return false;
    }
    /**
     * set crypted key for js decrypt
     */
    private static function setJScriptCryptKey() {

        if (defined('APPLICATION_JS_CRYPT_KEY')) {
            return;
        }
        $js_crypt_key = APPLICATION_CRYPT_KEY;
        $len          = strlen($js_crypt_key);
        $js_crypt_key = substr($js_crypt_key, $len / 2) . substr($js_crypt_key, 0, $len / 2);
        define('APPLICATION_JS_CRYPT_KEY', $js_crypt_key);
    }
    /**
     * before application shutdown
     */
    abstract public function beforeShutdown();
}
