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

use lightmvc\auth\AuthLogicInterface;
use lightmvc\context\RuntimeContext;
use lightmvc\core\Dispatcher;
use lightmvc\core\http\Request;
use lightmvc\core\http\Response;
use lightmvc\Encoding;
use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;
use lightmvc\info\InfoCollector;

class WebApplication extends Application
{
    protected static $_instance;
    protected $_auth_logic;
    protected $_error_handler = '/common/AppError/handle';

    /**
     * init
     * @throws AppException
     */
    public static function init()
    {
        parent::init();
        // load enviornment
        AppRuntimeContext::getInstance()->getAppEnvi();
        // fix encoding setting by user agent
        Useragent::getCarrier();
        // crypt key for javascript
        self::setJScriptCryptKey();
        // set session config
        Session::applyConfig(Application::getConfigByKey('application', 'session'));
    }
    /**
     * set application locale
     */
    public static function setApplicationLocale($lang = null)
    {

        parent::setApplicationLocale($lang);
        // set language info to cookie
        set_cookie_pro('lang', $lang, null, 3600 * 24 * 30);
    }
    /**
     * read maintenance config
     */
    protected function getMaintenanceConfigPath()
    {

        $config = APPLICATION_CONFIG_DIR;
        $envi = RuntimeContext::getInstance()->getAppEnvi();
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
            return null;
        }
        return $config;
    }
    /**
     * set auth logic
     * @param AuthLogicInterface $auth_logic
     * @throws AppException
     */
    public function setAuth(AuthLogicInterface $auth_logic)
    {
        if (empty($auth_logic)) {
            throw new AppException('auth logic should not be empty', ExceptionCode::APP_AUTH_CALLBACK_AUTH);
        }
        $this->_auth_logic = $auth_logic;
    }
    /**
     * set encoding config
     * @param array $config
     */
    public static function setEncodingConfig($config)
    {
        parent::setEncodingConfig($config);
        mb_http_output(Encoding::convertEncodingName($output_encode));
    }
    /**
     * run application
     * @throws AppException
     */
    public function run()
    {
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
        // loccalization
        static::setApplicationLocale();
        $maintenance_info = null;
        $current_path = Request::getCurrentUrlPath();
        __add_info(
            sprintf('==============url: %s', $current_path),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        try {
            if ($this->_auth_logic) {
                $auth = new Auth(
                    $this->_auth_logic,
                    ['url_path' => $current_path]
                );
                $auth->auth();
            }
            $dispatcher = $this->getDispatcher();
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
    protected function handleException($e)
    {
        RuntimeContext::getInstance()->setData('render_error_exception', $e);
        $dispatcher = $this->getDispatcher();
        $response_contents = $dispatcher->dispatch($this->_error_handler);
        $response = Response::getInstance();
        $response->setContents($response_contents);
        $response->send();
    }
    /**
     * get dispatcher
     * @return Dispatcher
     */
    private function getDispatcher()
    {
        if (self::$dispatcher !== null) {
            return self::$dispatcher;
        }
        self::$dispatcher = new Dispatcher();
        return self::$dispatcher;
    }
    /**
     * set crypted key for js decrypt
     */
    private static function setJScriptCryptKey()
    {
        if (defined('APPLICATION_JS_CRYPT_KEY')) {
            return;
        }
        $js_crypt_key = APPLICATION_CRYPT_KEY;
        $len = strlen($js_crypt_key);
        $js_crypt_key = substr($js_crypt_key, $len / 2) . substr($js_crypt_key, 0, $len / 2);
        define('APPLICATION_JS_CRYPT_KEY', $js_crypt_key);
    }
    /**
     * get path of the debug
     * @return string
     */
    protected static function getDebugConfig()
    {
        return APPLICATION_CONFIG_DIR . 'common/debug.php';
    }
    /**
     * get is debug enabled
     * @return bool
     */
    private static function getIsDebugEnabled()
    {
        $debug_enabled = static::getConfigByKey('debug', 'enable');
        if (!$debug_enabled) {
            return $debug_enabled;
        }
        if (!isset($_REQUEST['debug_secret'])) {
            return false;
        }
        $check_debug_secret = static::checkDebugSecret($_REQUEST['debug_secret']);
        if ($check_debug_secret) {
            return true;
        }
        return false;
    }
    /**
     * check debug secret
     * @return boolean
     */
    private static function checkDebugSecret($test_debug_secret)
    {
        if (empty($debug_secret)) {
            return false;
        }
        $debug_secret = static::getConfigByKey('debug', 'secret');
        return (bool) $debug_secret == $test_debug_secret;
    }
}
