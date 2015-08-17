<?php
use core\Dispatcher;
use auth\AuthLogicInterface;
use context\RuntimeContext,
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
 * locale:
 *     cookie -> user_config -> application_config
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
    private static $current_path;

    /**
     * constructor
     */
    protected function __construct($cli_manual_envi = null) {
        self::init($cli_manual_envi);
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
        // crypte key
        define('APPLICATION_CRYPTE_KEY', self::getConfigByKey('application', 'crypt_key'));
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
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($expanded_functions_dir)));
        foreach($objects as $entry => $object) {
            if(substr($entry, -4) != '.php') {
                continue;
            }
            $entry = str_replace('\\', '/', $entry);
            include $entry;
        }
    }
    /**
     * set application locale
     */
    public static function setApplicationLocale() {
        if(!defined('APPLICATION_LANG')) {
            $locale = self::getConfigByKey('application', 'locale');
            if(empty($locale)) {
                $locale = 'en_US';
            }
            define('APPLICATION_LANG', $locale);
        }
        if (substr(APPLICATION_LANG, 0 ,2) != 'en') {
            self::setLocale(APPLICATION_LANG, APPLICATION_DIR . 'locale');
        }
    }
    /**
     * set locale
     * @param $locale
     * @return void
     */
    public static function setLocale($locale, $dir, $module = null) {
        putenv('LANG=' . $locale);
        setlocale(LC_ALL, $locale);
        $domain = self::getProjectName();
        if($module) {
            $domain .= "_{$module}";
        }
        bind_textdomain_codeset($domain, 'UTF-8');
        bindtextdomain($domain, $dir);
        textdomain($domain);
        __add_info(sprintf('locale set to %s. domain: %s, dir: %s', $locale, $domain,  $dir),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
    }
    /**
     * singleton
     * @return Application
     */
    public static function getInstance($cli_manual_envi = null){
        if(static::$_instance !== null) {
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
            return ;
        }
        static::$_config[$category] = $config;
    }
    /**
     * remove application config
     * @param string $key
     */
    public static function removeConfig($category, $key = null) {
        if (!array_key_exists($category, static::$_config)) {
            return ;
        }
        if($key && array_key_exists($key, static::$_config[$category])) {
            unset(static::$_config[$category]);
            return ;
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
        self::$input_encoding = $input_encode;
        self::$output_encoding = $output_encode;
        // encoding settings
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        mb_http_output($output_encode);
    }
    /**
     * run application
     * @throws AppException
     */
    public function run() {

        if (!defined('APPLICATION_ENVI')) {
            throw new AppException('APPLICATION_ENVI not defined', ExceptionCode::APP_ENVI_NOT_DEFINED);
        }
        if (!defined('APPLICATION_CONFIG_DIR')) {
            throw new AppException('APPLICATION_CONFIG_DIR not defined', ExceptionCode::APP_CONFIG_DIR_NOT_DEFINED);
        }
        $current_path = self::getCurrentUrlPath();
        if ($this->_auth_logic) {
            $auth = new Auth($this->_auth_logic, array('url_path' => $current_path));
            $auth->auth();
        }
        // loccalization
        self::setApplicationLocale();
        $dispatcher = new Dispatcher();
        $dispatcher->dispatch($current_path);
    }
    /**
     * check url
     * @param string $url
     * @throws AppException
     */
    protected static function checkUrl($url) {
        if (empty($url)) {
            // throw exception when url empty
            throw new AppException('url empty', ExceptionCode::APP_URL_EMPTY);
        }
        $is_url_ok = validate_url($url);
        if (!$is_url_ok) {
            // throw exception when url invalid
            throw new AppException('invalid url: ' , $url, ExceptionCode::APP_URL_INVALID);
        }
        $url_array = parse_url($url);
        if (empty($url_array)) {
            // throw exception when url parse error
            throw new AppException('parse url error', ExceptionCode::APP_URL_PARSE_ERROR);
        }
        // check url by regular express
        $url_validation_config = APPLICATION_CONFIG_DIR . 'common/urlValidation.php';
        if (!is_file($url_validation_config)) {
            // no check if regular express check is not needed
            return true;
        }
        $url_validation_config = include $url_validation_config;
        if(empty($url_validation_config) || empty($url_validation_config['pairs'])) {
            // no check if regular express check is not needed
            return true;
        }
        $full_paramter_str = http_build_query(array_merge($_GET, $_POST));
        foreach ($url_validation_config['pairs'] as $url_pattern => $pattern_alias) {
            if (!preg_match($url_pattern, $url_array['path'])) {
                // not matched
                continue;
            }
            if($pattern_alias == 'no_check') {
                return true;
            }
            if(empty($url_validation_config['pattern'][$pattern_alias]))
                continue;
            // check params
            if(!preg_match($url_validation_config['pattern'][$pattern_alias],
                $full_paramter_str)) {
                throw new AppException('url parameter invalid', ExceptionCode::APP_URL_INVALID_PARAM);
            }
            return true;
        }
        return false;
    }
    /**
     * get current url path
     * @throws AppException
     */
    public static function getCurrentUrlPath() {
        if(self::$current_path !== null) {
            return self::$current_path;
        }
        if (RuntimeContext::getInstance()->getAppRunmode() == RuntimeContext::MODE_CLI) {
            global $argv;
            $url = empty($argv[1]) ? null : $argv[1];
        } else {
            $url = current_url();
        }
        if (!self::checkUrl($url)) {
            throw new AppException('invalid url: ' . $url, ExceptionCode::APP_URL_INVALID);
        }
        $url_array = parse_url($url);
        self::$current_path = empty($url_array['path']) ? '' : $url_array['path'];
        return self::$current_path;
    }
    /**
     * get crypted key for js decrypt
     */
    public static function getJScriptCryptKey() {
        if(defined('APPLICATION_JS_CRYPTE_KEY')) {
            return APPLICATION_JS_CRYPTE_KEY;
        }
        $js_crypt_key = APPLICATION_CRYPTE_KEY;
        $len = strlen($js_crypt_key);
        $js_crypt_key = substr($js_crypt_key, $len / 2  ) . substr($js_crypt_key, 0, $len / 2);
        define('APPLICATION_JS_CRYPTE_KEY', $js_crypt_key);
        return APPLICATION_JS_CRYPTE_KEY;
    }
}
