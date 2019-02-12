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
namespace lightmvc\batch;

use lightmvc\Auth;
use lightmvc\Application;
use lightmvc\ClassLoader;
use lightmvc\view\View;
use lightmvc\exception\BatchException;
use lightmvc\info\InfoCollector;
use lightmvc\exception\ExceptionCode;
use Exception;

abstract class BatchExec
{
    /**
     * Auth
     * @var Auth
     */
    protected $_auth;
    /**
     * batch name
     * @var string
     */
    private $batch_name;
    /**
     * view type:
     *     Smarty, Rain, Text, Json
     * @var string
     */
    private $view_type;
    /**
     * View
     * @var ViewInterface
     */
    private $view;
    /**
     * view param
     * @var array
     */
    private $view_param;
    /**
     * cache param: used for template rending
     * example:
     *      smarty: compile_id, cache_id
     * @var array
     */
    private $cache_param;
    /**
     * has output
     * @var false
     */
    private $has_output;
    /**
     * output type
     * @var int
     */
    private $output_type;
    /**
     * output file path
     * @var string
     */
    private $output_file;

    const OUTPUT_ECHO = 1;
    const OUTPUT_FILE = 2;

    /**
     * __constructor
     */
    public function __construct()
    {
        $this->_auth = null;
        $this->view= null;
        $this->view_param  = [];
        $this->cache_param = [];
        $this->has_output = false;
        $this->output_file = null;
        $this->setViewType(View::VIEW_TYPE_TEXT);
    }
    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->clearBatchExecVariables();
    }
    /**
     * [__clone description]
     */
    public function __clone()
    {
        $this->clearBatchExecVariables();
    }
    /**
     * clear all controller variables
     */
    private function clearBatchExecVariables()
    {
        $this->_auth = null;
        $this->cache_param = null;
        $this->batch_name = null;
        $this->view = null;
        $this->view_file_path = null;
        $this->view_param = null;
        $this->view_type = null;
        $this->has_output = false;
        $this->output_file = null;
    }
    /**
     * get view config
     * @throws BatchException
     */
    protected function getViewConfig()
    {
        $view_config = Application::getConfigByKey('batch_view');
        if (!empty($view_config)) {
            return $view_config;
        }
        $view_config = APPLICATION_CONFIG_DIR . 'batch'. DS . 'view.php';
        if (!is_file($view_config)) {
            throw new BatchException(
                'batch view config file not exist: ' . $view_config,
                ExceptionCode::BATCH_VIEW_CONFIG_NOT_EXIST
            );
        }
        $view_config = include $view_config;
        Application::setConfig('batch_view', $view_config);
        return $view_config;
    }
    /**
     * assign parameter to view
     * @param string $key
     * @param mixed $val
     */
    protected function setViewParam($key, $value)
    {
        $this->view_param[$key] = $value;
    }
    /**
     * get view parameter
     * @param string $key
     * @param mixed $val
     */
    protected function getViewParam($key)
    {
        if (!array_key_exists($key, $this->view_param)) {
            return null;
        }
        return $this->view_param[$key];
    }
    /**
     * remove view parameter
     * @param string $key
     * @param mixed $val
     */
    protected function removeViewParam($key)
    {
        if (!array_key_exists($key, $this->view_param)) {
            return;
        }
        unset($this->view_param[$key]);
    }
    /**
     * set view type
     * @param string $view_type
     */
    final protected function setViewType($view_type)
    {
        $this->view_type = $view_type;
        // message
        __add_info(
            sprintf('batch view type has been set to: %s', $view_type),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
    }
    /**
     * get view type
     */
    final protected function getViewType()
    {
        return $this->view_type;
    }
    /**
     * get batch name
     */
    final public function setBatchName($batch_name)
    {
        $this->batch_name= $batch_name;
    }
    /**
     * get batch name
     */
    final public function getBatchName()
    {
        return $this->batch_name;
    }
    /**
     * get view type
     */
    final protected function setOutputType($output_type)
    {
        $this->output_type= $output_type;
    }
    /**
     * get output type
     */
    final protected function getOutputType()
    {
        return $this->output_type;
    }
    /**
     * get view type
     */
    final protected function setHasoutput($has_output)
    {
        $this->has_output = $has_output;
    }
    /**
     * get view type
     */
    final protected function getHasoutput()
    {
        return $this->has_output;
    }
    /**
     * get view type
     */
    final protected function setOutputFile($output_file)
    {
        $this->output_file= $output_file;
    }
    /**
     * get view type
     */
    final protected function getOutputFile()
    {
        return $this->output_file;
    }
    /**
     * set view
     * @throws BatchException
     */
    final protected function setView($view_type)
    {
        switch ($view_type) {
            case View::VIEW_TYPE_SMARTY:
                $this->view = ClassLoader::loadClass('\lightmvc\view\SmartyView');
                break;
            case View::VIEW_TYPE_RAIN:
                $this->view = ClassLoader::loadClass('\lightmvc\view\RainView');
                break;
            case View::VIEW_TYPE_JSON:
                $this->view = ClassLoader::loadClass('\lightmvc\view\JsonView');
                break;
            case View::VIEW_TYPE_TEXT:
                $this->view = ClassLoader::loadClass('\lightmvc\view\TextView');
                break;
            default:
                throw new BatchException(
                    'batch view type not support: ' . $view_type,
                    ExceptionCode::BATCH_VIEW_NOT_SUPPORT
                );
        }
        if ($this->view) {
            // message
            __add_info(sprintf('view created: %s', $view_type), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
    }
    /**
     * render
     */
    final public function render()
    {
        if (empty($this->view)) {
            $view_config = $this->getViewConfig();
            $this->setViewType($view_config['type']);
            $this->setView($this->view_type);
            if (empty($this->view)) {
                throw new BatchException('set batch view failed', ExceptionCode::BATCH_VIEW_FAILED);
            }
            $this->view->init($view_config);
        }
        if (empty($this->view_file_path) && in_array(
            $this->view_type,
            [
                View::VIEW_TYPE_RAIN,
                View::VIEW_TYPE_SMARTY,
            ]
        )) {
            $this->view_file_path = $template_file_path;
        }
        $output = $this->view->render($this->view_file_path, $this->view_param, $this->cache_param);
        echo_pro($output);
    }
    /**
     * if use smarty or rain view, we need template file
     *
     * @return string
     */
    protected function setViewFilePath($template_file_path)
    {
        $this->view_file_path = $template_file_path;
    }
    public function setAuth(Auth $auth)
    {
        $this->_auth = $auth;
    }

    public function setup()
    {
    }

    public function tearDown()
    {
    }

    public function run($args)
    {
        if ($this->_auth) {
            $this->auth();
        }
        $output = null;
        ob_start();
        try {
            $this->setup();
            $this->exec($args);
            if ($this->has_output) {
                $this->render();
            }
            $this->tearDown();
        } catch (Exception $e) {
            ob_flush();
            throw $e;
        }
        $output = ob_get_contents();
        ob_end_clean();
        if ($this->has_output) {
            switch ($this->output_type) {
                case self::OUTPUT_FILE:
                    if (!is_file($this->output_file)) {
                        throw new BatchException(
                            'batch output file not exists: ' . $this->output_file,
                            ExceptionCode::BATCH_OUTPUT_FILE_NOT_EXIST
                        );
                    }
                    file_put_contents($output, $this->output_file);
                    break;
                default:
                    echo_pro($output);
                    break;
            }
        }
    }
    /**
     * exec
     * @return
     */
    abstract public function exec($args);
}
