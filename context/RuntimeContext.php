<?php
namespace lightmvc\context;

use lightmvc\context\Context;
use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;

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
 * Runtime Context
 * =======================================================
 * Runtime context
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class RuntimeContext extends Context
{

    const ENVI_PRODUCTION = 'production';
    const ENVI_DEVELOP = 'develop';
    const ENVI_STAGING = 'staging';

    const MODE_NORMAL = 'n';
    const MODE_CLI = 'c';

    // application run environment
    protected static $APP_ENVI;
    // application run mode
    protected static $APP_RUN_MODE;
    // set is application debug mode
    protected static $IS_DEBUG_MODE = false;

    /**
     * constructor
     */
    protected function __construct()
    {
        parent::__construct();
        $this->getAppRunmode();
    }
    /**
     * get application runtime enviornment
     * @param string $manual_envi: specify custom environment when run in cli mode
     * @return string
     */
    public function getAppEnvi($manual_envi = null)
    {

        if (!self::$APP_ENVI) {
            return self::$APP_ENVI;
        }
        $this->detectAppEnvi($manual_envi);
        if (!self::$APP_ENVI || !in_array(self::$APP_ENVI, [self::ENVI_PRODUCTION, self::ENVI_STAGING, self::ENVI_DEVELOP])) {
            throw new AppException('set application environment failed', ExceptionCode::APP_ENVI_NOT_DEFINED);
        }
        define('APPLICATION_ENVI', self::$APP_ENVI);
        $this->setupAppEnvi();
        return static::$APP_ENVI;
    }
    /**
     * get application run mode
     */
    public function getAppRunmode()
    {
        if (self::$APP_RUN_MODE) {
            return self::$APP_RUN_MODE;
        }
        if (defined('PHP_SAPI') && PHP_SAPI == 'cli') {
            self::$APP_RUN_MODE = self::MODE_CLI;
        } else {
            self::$APP_RUN_MODE = self::MODE_NORMAL;
        }
        return self::$APP_RUN_MODE;
    }
    /**
     * set application environment
     */
    protected function setupAppEnvi()
    {
        switch (self::$APP_ENVI) {
            case self::ENVI_PRODUCTION:
                ini_set('display_errors', false);
                ini_set('display_startup_errors', true);
                ini_set('log_errors', true);
                break;
            case self::ENVI_STAGING:
                ini_set('display_errors', false);
                ini_set('display_startup_errors', false);
                ini_set('log_errors', true);
                break;
            default:
                error_reporting(E_ALL | E_STRICT);
                ini_set('display_errors', true);
                ini_set('display_startup_errors', true);
                ini_set('log_errors', true);
                break;
        }
    }
    /**
     * detect application enviornment
     * @param  string $manual_envi
     */
    protected function detectAppEnvi($manual_envi)
    {

        static::$APP_ENVI = self::ENVI_DEVELOP;
        // batch application
        if ($this->getAppRunmode() == self::MODE_CLI && $manual_envi && in_array($manual_envi, [self::ENVI_PRODUCTION, self::ENVI_STAGING, self::ENVI_DEVELOP])
        ) {
            static::$APP_ENVI = $manual_envi;
            return;
        }
        // web application
        $app_envi = isset($_SERVER['APPLICATION_ENVI']) ? strtolower($_SERVER['APPLICATION_ENVI']) : '';
        switch ($application_context) {
            case self::ENVI_PRODUCTION:
                static::$APP_ENVI = RuntimeContext::ENVI_PRODUCTION;
                break;
            case self::ENVI_STAGING:
                static::$APP_ENVI = RuntimeContext::ENVI_STAGING;
                break;
            default:
                static::$APP_ENVI = RuntimeContext::ENVI_DEVELOP;
                break;
        }
    }
}
