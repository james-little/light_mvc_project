<?php

/**
 * Copyright 2016 Koketsu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
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
namespace lightmvc;

use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;

abstract class Application
{

    protected static $_config;
    protected static $input_encoding;
    protected static $output_encoding;
    protected $_error_handler;

    const LANG_EN = 1;
    const LANG_JP = 2;
    const LANG_CN = 3;

    /**
     * constructor
     */
    protected function __construct()
    {
        static::$_config = [];
        static::init();
        // shutdown handler
        register_shutdown_function([$this, 'beforeShutdown']);
    }
    /**
     * init
     * @throws AppException
     */
    public static function init()
    {
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
        static::includeFrameworkFunctions();
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
                sprintf('application config not found in: %s', $application_config),
                ExceptionCode::APP_CONFIG_FILE_NOT_EXIST
            );
        }
        $application_config = require $application_config;
        static::setEncodingConfig($application_config['encode']);
        // timezone
        date_default_timezone_set($application_config['time_zone']);
        // put config settings intp application context
        static::setConfig('application', $application_config);
        // debug config
        static::setDebugInfo();
        // crypt key
        define('APPLICATION_CRYPT_KEY', static::getConfigByKey('application', 'crypt_key', 'key'));
        define('APPLICATION_CRYPT_IV', static::getConfigByKey('application', 'crypt_key', 'iv'));
        // set project log on/off
        define('APPLICATION_KPI_LOG', static::getConfigByKey('application', 'project_log', 'enabled'));
        define('APPLICATION_COMPRESS_ENABLED', static::getConfigByKey('application', 'enable_compressing'));
    }

    /**
     * include framework functions
     */
    public static function includeFrameworkFunctions()
    {
        $expanded_functions_dir = __DIR__ . DIRECTORY_SEPARATOR . 'functions';
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($expanded_functions_dir)));
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
    public static function setApplicationLocale($lang = null)
    {
        if ($lang && !in_array($lang, [
            static::LANG_EN, static::LANG_JP, static::LANG_CN,
        ])) {
            return;
        }
        if (!$lang) {
            $locale = static::getConfigByKey('application', 'locale');
            $lang = static::getLangByLocale($locale);
        }
        if (!defined('APPLICATION_LANG')) {
            define('APPLICATION_LANG', $lang);
            static::setLocaleDomain($lang, APPLICATION_DIR . 'locale');
        }
    }
    /**
     * get language list
     * @return array
     */
    public static function getLangList()
    {
        return [
            static::LANG_EN => __message("English"),
            static::LANG_JP => __message("Japanese"),
            static::LANG_CN => __message("Chinese"),
        ];
    }
    /**
     * get locale name by language
     * @param  int $lang
     * @return string
     */
    public static function getLocaleByLang($lang)
    {
        switch ($lang) {
            case static::LANG_JP:
                return 'ja-JP';
            case static::LANG_CN:
                return 'zh-CN';
        }
        return 'en-US';
    }
    /**
     * get lang by locale
     * @param  string $locale
     * @return int
     */
    public static function getLangByLocale($locale)
    {
        switch ($locale) {
            case 'ja-JP':
                return static::LANG_JP;
            case 'zh-CN':
                return static::LANG_CN;
        }
        return static::LANG_EN;
    }
    /**
     * set locale
     * @param $lang
     * @return void
     */
    public static function setLocaleDomain($lang, $dir, $module = null)
    {
        if ($lang == static::LANG_EN) {
            return;
        }
        $locale = str_replace('-', '_', static::getLocaleByLang($lang));
        putenv('LANG=' . $locale);
        setlocale(LC_ALL, $locale);
        $domain = static::getProjectName();
        if ($module) {
            $domain .= "_{$module}";
        }
        bind_textdomain_codeset($domain, 'UTF-8');
        bindtextdomain($domain, $dir);
        textdomain($domain);
        __add_info(
            sprintf('locale set to %s. domain: %s, dir: %s', $locale, $domain, $dir),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
    }
    /**
     * singleton
     * @return Application
     */
    public static function getInstance()
    {

        if (static::$_instance !== null) {
            return static::$_instance;
        }
        static::$_instance = new static();
        return static::$_instance;
    }
    /**
     * set resource config
     * @param string $key
     * @param array $config
     */
    public static function setConfig($category, $config)
    {

        if (empty($category)) {
            return;
        }
        static::$_config[$category] = $config;
    }
    /**
     * remove application config
     * @param string $key
     */
    public static function removeConfig($category, $key = null)
    {

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
    public static function getConfig()
    {
        return static::$_config;
    }
    /**
     * get config
     */
    public static function getConfigByKey($category, $key = null, $sec_key = null)
    {
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
    public static function getProjectName()
    {
        return static::getConfigByKey('application', 'project_name');
    }
    /**
     * get project log base dir
     */
    public static function getLogBaseDir()
    {
        $log_base_dir = static::getConfigByKey('application', 'log_base_dir');
        $log_base_dir = str_replace('%project_name%', static::getProjectName(), $log_base_dir);
        return $log_base_dir;
    }
    /**
     * get input encoding
     */
    public static function getInputEncoding()
    {
        return static::$input_encoding;
    }
    /**
     * get output encoding
     */
    public static function getOutputEncoding()
    {
        return static::$output_encoding;
    }
    /**
     * set encoding config
     * @param array $config
     */
    public static function setEncodingConfig($config)
    {
        $default_encode = Encoding::convertEncodingName(Encoding::ENCODE_UTF8);
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
        static::$input_encoding = $input_encode;
        static::$output_encoding = $output_encode;
        // encoding settings
        mb_internal_encoding(Encoding::convertEncodingName(Encoding::ENCODE_UTF8));
        mb_regex_encoding(Encoding::convertEncodingName(Encoding::ENCODE_UTF8));
    }
    /**
     * read maintenance config
     */
    protected function getMaintenanceConfig()
    {
        $config_path = $this->getMaintenanceConfigPath();
        if (!$config_path) {
            return null;
        }
        $config = include $config_path;
        return $config;
    }
    /**
     * is in maintenance
     * @return bool
     */
    public function isInMaintenance(&$maintenance_info = null)
    {
        $maintenance_config = $this->getMaintenanceConfig();
        if (empty($maintenance_config)) {
            if ($maintenance_info) {
                $maintenance_info = [
                    'start_time' => 0,
                    'end_time' => 0,
                ];
            }
            return false;
        }
        $start_time = null;
        $end_time = null;
        if (empty($maintenance_config['start_time'])) {
            if ($maintenance_info) {
                $maintenance_info['start_time'] = '';
            }
        } else {
            $start_time = strtotime($maintenance_config['start_time']);
            if ($maintenance_info) {
                $maintenance_info['start_time'] = $maintenance_config['start_time'];
            }
        }
        if (!empty($maintenance_config['end_time'])) {
            if ($maintenance_info) {
                $maintenance_info['end_time'] = '';
            }
        } else {
            $end_time = strtotime($maintenance_config['end_time']);
            if ($maintenance_info) {
                $maintenance_info['end_time'] = $maintenance_config['end_time'];
            }
        }
        $now_time = time();
        if (!$start_time) {
            if (!$end_time) {
                if ($now_time >= $start_time && $now_time < $end_time) {
                    return true;
                }
                return false;
            }
            if ($now_time >= $start_time) {
                return true;
            }
            return false;
        }
        if (!$end_time) {
            if ($now_time < $end_time) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * set debug info collector for production/staging environment
     */
    private static function setDebugInfo()
    {
        $debug_config = static::getDebugConfig();
        if (!is_file($debug_config)) {
            throw new AppException(
                sprintf('debug config file not exist: %s', $debug_config),
                ExceptionCode::APP_DEBUG_CONFIG_FILE_NOT_EXIST
            );
        }
        $debug_config = require $debug_config;
        if (!array_key_exists('enable', $debug_config)) {
            $debug_config['enable'] = false;
        }
        static::setConfig('debug', $debug_config);

        $debug_enabled = static::getIsDebugEnabled();
        // disable information collection when in production mode by default
        $is_enable_ic = static::getIsInfoCollectEnabled();
        // enable info collect
        defined('ENABLE_INFO_COLLECT') ? null : define('ENABLE_INFO_COLLECT', $is_enable_ic);
        // debug mode
        defined('APPLICATION_IS_DEBUG') ? null : define('APPLICATION_IS_DEBUG', $is_debug);
    }
    /**
     * get is debug enabled
     * @return bool
     */
    private static function getIsDebugEnabled()
    {
        return static::getConfigByKey('debug', 'enable');
    }
    /**
     * get is information collection to collect all information
     * @return bool
     */
    private static function getIsInfoCollectEnabled()
    {
        return RuntimeContext::$APP_ENVI == RuntimeContext::ENVI_PRODUCTION ? false : true;
    }
    /**
     * before application shutdown
     */
    public function beforeShutdown()
    {
        global $GLOBALS;
        $info_collector = empty($GLOBALS['information_collector']) ? null : $GLOBALS['information_collector'];
        if ($info_collector) {
            // render collected information
            $info_render = new InfoRender($info_collector);
            $info_render->addAdapter(ClassLoader::loadClass('\info\adapter\InfoRenderAdapterException'));
            // add debug adapter when in develop enviornment
            if (defined('APPLICATION_IS_DEBUG') && APPLICATION_IS_DEBUG) {
                $info_render->addAdapter(ClassLoader::loadClass('\info\adapter\InfoRenderAdapterDebug'));
            }
            $info_render->render();
        }
    }
    /**
     * get path of the debug
     * @return string
     */
    abstract protected static function getDebugConfig();
    /**
     * set auth logic
     * @param AuthLogicInterface $auth_logic
     * @throws AppException
     */
    abstract public function setAuth(AuthLogicInterface $auth_logic);
    /**
     * run application
     * @throws AppException
     */
    abstract public function run();
    /**
     * maintenance config
     */
    abstract protected function getMaintenanceConfigPath();
}
