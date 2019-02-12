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
use lightmvc\exception\AppException;
use lightmvc\exception\BatchException;
use lightmvc\exception\ExceptionCode;
use lightmvc\Monitor;
use lightmvc\info\InfoCollector;

class BatchApplication extends Application
{
    protected static $_instance;
    protected static $script_name;
    protected static $app_envi;
    protected static $debug_enable;
    protected static $batch_name;
    protected static $args;

    /**
     * constructor
     */
    protected function __construct()
    {
        $parsed_args = $this->parseArgs();
        static::$script_name = $parsed_args['script_name'];
        static::$batch_name = $parsed_args['batch_name'];
        static::$app_envi = $parsed_args['app_envi'];
        static::$debug_enable = $parsed_args['debug_enable'];
        static::$args = $parsed_args['args'];
        parent::__construct();
    }
    /**
     * parse args
     * @return array
     */
    private function parseArgs()
    {
        global $argv;
        $parameters = $argv;
        $script_name = array_shift($parameters);
        $batch_name = array_shift($parameters);
        $app_envi = array_shift($parameters);
        $debug_enable = array_shift($parameters);
        return [
            'script_name' => $script_name,
            'app_envi' => $app_envi,
            'debug_enable' => $debug_enable,
            'batch_name' => $batch_name,
            'args' => $parameters,
        ];
    }
    /**
     * init
     * @throws AppException
     */
    public static function init()
    {
        parent::init();
        // config directory
        define('APPLICATION_BATCH_DIR', APPLICATION_DIR . 'batch' . DS);
        // load enviornment
        AppRuntimeContext::getInstance()->getAppEnvi(static::$app_envi);
    }

    /**
     * get path of the debug
     * @return string
     */
    protected static function getDebugConfig()
    {
        return APPLICATION_CONFIG_DIR . 'batch/debug.php';
    }
    /**
     * getmaintenance config
     */
    protected function getMaintenanceConfigPath()
    {
        return APPLICATION_CONFIG_DIR . 'batch/maintenance.php';
    }
    /**
     * set auth logic
     * @param AuthLogicInterface $auth_logic
     * @throws AppException
     */
    public function setAuth(AuthLogicInterface $auth_logic)
    {
        if (empty($auth_logic)) {
            return;
        }
        $this->_auth_logic = $auth_logic;
    }
    /**
     * run application
     * @throws AppException
     */
    public function run()
    {
        Monitor::reset();
        $maintenance_info = [];
        if ($this->isInMaintenance($maintenance_info)) {
            throw new AppException(
                sprinf(
                    'application is in maintenance: %s ~ %s',
                    $maintenance_info['start_time'],
                    $maintenance_info['end_time']
                ),
                ExceptionCode::APP_IN_MAINTENANCE
            );
            return;
        }
        // loccalization
        static::setApplicationLocale();
        $maintenance_info = null;
        $batch_path = APPLICATION_BATCH_DIR . static::$batch_name . '.php';
        if (!is_file($batch_path)) {
            throw new BatchException(
                sprinf('batch not exists: %s', static::$batch_name),
                ExceptionCode::BATCH_NOT_EXIST
            );
            return;
        }
        include $batch_path;
        $batch_exec = ClassLoader::loadClass(static::$batch_name);
        $batch_name = substr(static::$batch_name, strrpos(static::$batch_name, '/') + 1);
        $batch_exec->setBatchName($batch_name);
        try {
            if ($this->_auth_logic) {
                $batch_exec->setAuth(
                    new Auth($this->_auth_logic, ['batch_name' => $batch_name])
                );
            }
            $batch_exec->run(self::$args);
        } catch (Exception $e) {
            $execute_time = Monitor::stop();
            __add_info(
                sprintf(
                    'batch execute complete. time cost: %s',
                    $execute_time
                ),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            throw $e;
        }
        $execute_time = Monitor::stop();
        __add_info(
            sprintf(
                'batch execute complete. time cost: %s',
                $execute_time
            ),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
    }
}
