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
 * Controller
 * =======================================================
 * Controller manages a set of actions which deal with the corresponding user requests.
 * Through the actions, Controller coordinates the data flow between models and views.
 *
 * The Following functions defined for dispatch loop by Dispatcher:
 * . preFilter(): before the action is called
 * . postFilter(): after the action is called
 * . preRender(): before do template rendering
 * . postRender(): after do template rendering
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\core;

use lightmvc\Application;
use lightmvc\ClassLoader;
use lightmvc\core\http\Request;
use lightmvc\core\http\Response;
use lightmvc\Datatype;
use lightmvc\ErrorMessage;
use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;
use lightmvc\exception\ViewException;
use lightmvc\info\InfoCollector;
use lightmvc\Validator;
use lightmvc\view\View;

abstract class Controller
{
    /**
     * cache param: used for template rending
     * example:
     *      smarty: compile_id, cache_id
     * @var array
     */
    protected $_cache_param;
    /**
     * _GET and _POST params from server
     * @var Request
     */
    protected $_request;
    /**
     * View
     * @var ViewInterface
     */
    protected $_view;
    /**
     * view type:
     *     Smarty, Rain, Text, Json
     * @var string
     */
    protected $_view_type;
    /**
     * view file path
     * @var string
     */
    protected $_view_file_path;
    /**
     * view param
     * @var array
     */
    protected $_view_param;
    /**
     * controller render params
     * @var array
     */
    protected $_controller_render_params;
    /**
     * error message
     * @var ErrorMessage
     */
    protected $_error_message;
    /**
     * validator
     * @var Validator
     */
    protected $_validator;
    /**
     * __constructor
     */
    public function __construct()
    {
        $this->_request = Request::getInstance();
        // set possible controller render params
        $this->setRenderControllerParams();
        $view_config = $this->getViewConfig();
        $this->setViewType($view_config['type']);
        $this->_view_param    = [];
        $this->_cache_param   = [];
        $this->_error_message = ErrorMessage::getInstance();
        $this->_validator     = Validator::getInstance();
    }
    /**
     * set controller render params
     */
    private function setRenderControllerParams()
    {
        global $GLOBAL;
        if (empty($GLOBAL['render_controller_param'])) {
            return;
        }
        $this->_controller_render_params = [];
        foreach ($GLOBAL['render_controller_param'] as $key => $value) {
            $this->_controller_render_params[$key] = $value;
        }
        unset($GLOBAL['render_controller_param']);
    }
    /**
     * get paramter value from request parameters
     * @param string $key
     * @param mixed $default
     * @param int $data_type
     * @param string $method
     * @return mixed
     */
    public function getParam($key, $default, $data_type = Datatype::DATA_TYPE_STRING, $method = null)
    {

        // get from _GET OR _POST
        $val = $this->_request->getParam($key, $default, $data_type, $method);
        if (empty($val)
            && is_array($this->_controller_render_params)
            && array_key_exists($key, $this->_controller_render_params)) {
            // get from controller rendering params
            $val = $this->_controller_render_params[$key];
        }
        // message
        __add_info(
            sprintf('get param: %s => %s', $key, var_export($val, true)),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $val;
    }
    /**
     * assign parameter to view
     * @param string $key
     * @param mixed $val
     */
    protected function setViewParam($key, $value)
    {
        $this->_view_param[$key] = $value;
    }
    /**
     * get view parameter
     * @param string $key
     * @param mixed $val
     */
    protected function getViewParam($key)
    {
        if (!array_key_exists($key, $this->_view_param)) {
            return null;
        }
        return $this->_view_param[$key];
    }
    /**
     * remove view parameter
     * @param string $key
     * @param mixed $val
     */
    protected function removeViewParam($key)
    {
        if (!array_key_exists($key, $this->_view_param)) {
            return;
        }
        unset($this->_view_param[$key]);
    }
    /**
     * set view type
     * @param string $view_type
     */
    final protected function setViewType($view_type)
    {
        $this->_view_type = $view_type;
        // message
        __add_info(
            sprintf('view type has been set to: %s', $view_type),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
    }
    /**
     * get view type
     */
    final protected function getViewType()
    {
        return $this->_view_type;
    }
    /**
     * set view
     * @throws ViewException
     */
    final protected function setView($view_type)
    {
        switch ($view_type) {
            case View::VIEW_TYPE_SMARTY:
                $this->_view = ClassLoader::loadClass('\lightmvc\view\SmartyView');
                break;
            case View::VIEW_TYPE_RAIN:
                $this->_view = ClassLoader::loadClass('\lightmvc\view\RainView');
                break;
            case View::VIEW_TYPE_JSON:
                $this->_view = ClassLoader::loadClass('\lightmvc\view\JsonView');
                break;
            case View::VIEW_TYPE_TEXT:
                $this->_view = ClassLoader::loadClass('\lightmvc\view\TextView');
                break;
            default:
                throw new ViewException(
                    'view type not support: ' . $view_type,
                    ExceptionCode::VIEW_TYPE_NOT_SUPPORT
                );
        }
        if ($this->_view) {
            // message
            __add_info(sprintf('view created: %s', $view_type), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
    }
    /**
     * get view config
     * @throws ViewException
     */
    protected function getViewConfig()
    {
        $view_config = Application::getConfigByKey('view');
        if (!empty($view_config)) {
            return $view_config;
        }
        $view_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'view.php';
        if (!is_file($view_config)) {
            throw new ViewException(
                'config file not exist: ' . $view_config,
                ExceptionCode::VIEW_CONFIG_FILE_NOT_FOUND
            );
        }
        $view_config = include $view_config;
        Application::setConfig('view', $view_config);
        return $view_config;
    }
    /**
     * render
     */
    final public function render($module, $controller_class, $action_method)
    {
        if (empty($this->_view)) {
            $this->setView($this->_view_type);
            if (empty($this->_view)) {
                throw new ViewException('set view failed', ExceptionCode::VIEW_EMPTY);
            }
            $this->_view->init($this->getViewConfig());
        }
        if (empty($this->_view_file_path) && in_array(
            $this->_view_type,
            [
                View::VIEW_TYPE_RAIN,
                View::VIEW_TYPE_SMARTY,
            ]
        )) {
            $this->_view_file_path = $this->getViewFilePath($module, $controller_class, $action_method);
        }
        $response = Response::getInstance();
        $response->setIsCompress(defined('APPLICATION_COMPRESS_ENABLED') && APPLICATION_COMPRESS_ENABLED);
        // output encode
        $output_encode = $this->_view->getOutputEncode();
        switch ($this->_view_type) {
            case View::VIEW_TYPE_SMARTY:
            case View::VIEW_TYPE_RAIN:
                $response->setHeader('Content-Type', "text/html; charset={$output_encode}");
                break;
            case View::VIEW_TYPE_TEXT:
                $response->setHeader('Content-Type', "text/plain; charset={$output_encode}");
                break;
            case View::VIEW_TYPE_JSON:
                $response->setHeader('Cache-Control', 'no-cache, must-revalidate');
                $response->setHeader('Content-Type', "text/json; charset={$output_encode}");
                break;
            default:
                throw new ViewException(
                    'view type not support: ' . $this->_view_type,
                    ExceptionCode::VIEW_TYPE_NOT_SUPPORT
                );
        }
        // output contents
        $output = $this->_view->render($this->_view_file_path, $this->_view_param, $this->_cache_param);
        echo_pro($output);
    }
    /**
     * if use smarty or rain view, we need template file
     * user\controller\QueryController::search() -> user/query/search.tpl by default
     * @param  string $module
     * @param  string $controller_class
     * @param  string $action_method
     * @return string
     */
    protected function getViewFilePath($module, $controller_class, $action_method)
    {
        $controller_class = lcfirst(substr($controller_class, strrpos($controller_class, '\\') + 1, -10));
        return "{$module}/{$controller_class}/{$action_method}.tpl";
    }
    /**
     * render controller
     * @param string $controller_class
     * @param string $action_method
     * @param array | null $param
     */
    final public function renderController(
        $module,
        $controller = null,
        $action_method = null,
        array $param = null
    ) {
        global $GLOBAL;
        if ($controller === null) {
            $controller = 'Index';
        }
        $controller       = ucfirst($controller);
        $controller_class = "\\{$module}\\controller\\{$controller}Controller";
        if ($action_method === null) {
            $action_method = 'index';
        }
        $GLOBAL['controller_class'] = $controller_class;
        $GLOBAL['action_method']    = $action_method;
        // mark render controller
        $GLOBAL['is_controller_render_controller'] = 1;
        // set render controller param
        $GLOBAL['render_controller_param'] = $param;
    }
    /**
     * redirect
     * @param string $url
     * @param mixed $params
     */
    final protected function redirect($url, $params = null)
    {
        redirect($url, $params);
    }
    /**
     * render to error page when exception occurs
     * default: find ErrorController under the same directory
     * @param Exception $e
     */
    final public function renderError($e)
    {
        $class_name = get_class($this);
        $module     = ucfirst(substr($class_name, 0, strpos($class_name, '\\')));
        $this->renderController(
            $module,
            'Error',
            'handle',
            ['render_error_exception' => $e]
        );
    }
    /**
     * get is ajax request
     * @return boolean
     */
    final public function isAjaxRequest()
    {
        return Request::isAjaxRequest();
    }
    /**
     * check request method
     *
     * @int $request_method
     * @return boolean
     */
    final public function isRequestMethod($request_method)
    {
        return Request::getRequestMethod() === $request_method;
    }
    /**
     * get error message
     * @param  int $error_code
     * @return string
     */
    protected function getErrorMessage($error_code)
    {
        return $this->_error_message->getErrorMessage($error_code);
    }
    /**
     * __call
     * @param string $name
     * @param array $arguments
     */
    final public function __call($name, $arguments)
    {
        throw new AppException('action not callable:' . $name, ExceptionCode::APP_ACTION_NOT_CALLABLE);
    }
    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->clearControllerVariables();
    }
    /**
     * [__clone description]
     */
    public function __clone()
    {
        $this->clearControllerVariables();
    }
    /**
     * convert method name to method name in use
     *  ax_bx_cx -> aBxCx
     */
    private function convertMethodName($method_name)
    {
        if (!preg_match('#^([a-z]+_)+[a-z]+$#', $method_name)) {
            return $method_name;
        }
        $method_name_word_list = explode('_', $method_name);
        foreach ($method_name_word_list as $key => $method_name_word) {
            if (!$key) {
                continue;
            }
            $method_name_word_list[$key] = ucfirst($method_name_word);
        }
        return implode('', $method_name_word_list);
    }
    /**
     * clear all controller variables
     */
    private function clearControllerVariables()
    {
        $this->_cache_param              = null;
        $this->_controller_render_params = null;
        $this->_request                  = null;
        $this->_view                     = null;
        $this->_view_file_path           = null;
        $this->_view_param               = null;
        $this->_view_type                = null;
    }
    /**
     * put var to map
     * @param  Map &$map
     * @param  string $key
     * @param  mixed $val
     * @param  bool $is_force
     */
    protected function putVarToMap(&$map, $key, $val, $is_force = true)
    {
        if ($is_force) {
            $map[$key] = $val;
            return;
        }
        if ($val !== null) {
            $map[$key] = $val;
        }
    }
    /**
     * put var to map
     * @param  Map &$map
     * @param  string $key
     * @param  mixed $val
     * @param  bool $is_force
     */
    protected function putVarToMapNotEmpty(&$map, $key, $val, $is_force = false)
    {
        if ($is_force) {
            $map[$key] = $val;
            return;
        }
        if (!empty($val)) {
            $map[$key] = $val;
        }
    }
    /**
     * validate parameter
     * @param string $variable_name
     * @param mixed $value
     * @param string $rule
     * @param int $type
     * @param string $reg
     * @return boolean
     */
    protected function validate($variable_name, $value, $rule, $type, $reg = null)
    {
        $validate_result_list = $this->_validator->validate([
            $variable_name => [
                'value' => $value,
                'rule'  => $rule,
                'type'  => $type,
                'reg'   => $reg,
            ],
        ]);
        return $validate_result_list[$variable_name];
    }
    /**
     * validate data map
     * @param array $validate_data_map
     *   - must be like: array(
     *       %variable_name1% => array('value' => %variable_value1%, 'rule' => %rule1%, 'type' => %type1%, 'reg' => %reg1%),
     *       %variable_name2% => array('value' => %variable_value2%, 'rule' => %rule2%, 'type' => %type2%, 'reg' => %reg2%),
     *       ...
     *   )
     *   rule:
     *       1. split by ';' when multi-condition
     *       2. sample:
     *              $var != null         : not null
     *               !empty($var)        : not empty
     *              $var >(=) 2          : >(=) 2
     *              $var <(=) 100        : <(=) 100
     *              $var > 50
     *              && $var < 100        : > 50 and < 100
     *              $var < 50
     *              || $var > 100        : < 50 or > 100
     *              in_array(1,2)        : in list[1,2]
     *              !in_array(1,2)       : not in list [1,2]
     *              strlen(x) >(=) 10    : len >(=) 10
     *              count(x) >(=) 10     : count >(=) 10
     * @return array (
     *     %variable_name1% => %validate_variable_result1%,
     *     %variable_name2% => %validate_variable_result2%
     *     ...
     * )
     */
    protected function validateDataMap($validate_data_map)
    {
        return $this->_validator->validate($validate_data_map);
    }
    /**
     * prefilter
     */
    public function preFilter($module, $action_method)
    {
    }
    /**
     * postfilter
     */
    public function postFilter($module, $action_method)
    {
    }
    /**
     * preRender
     */
    public function preRender($module, $action_method)
    {
    }
    /**
     * postRender
     */
    public function postRender($module, $action_method)
    {
    }
}
