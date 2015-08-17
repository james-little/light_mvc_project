<?php
namespace core;

use \ClassLoader,
    \Application,
    \Datatype,
    core\http\Parameter,
    info\InfoCollector,
    exception\ViewException,
    view\ViewInterface,
    exception\ExceptionCode;

/**
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

abstract class Controller {

    /**
     * cache param: used for template rending
     * example:
     *      smarty: compile_id, cache_id
     * @var array
     */
    protected $_cache_param;
    /**
     * _GET and _POST params from server
     * @var Parameter
     */
    protected $_param;
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
     * __constructor
     */
    public function __construct() {
        $this->_param = Parameter::getInstance();
        // set possible controller render params
        $this->setRenderControllerParams();
        $view_config = $this->getViewConfig();
        $this->setViewType($view_config['type']);
        $this->_view_param = array();
    }
    /**
     * set controller render params
     */
    private function setRenderControllerParams() {
        global $GLOBAL;
        if (empty($GLOBAL['render_controller_param'])) {
            return ;
        }
        $this->_controller_render_params = array();
        foreach ($GLOBAL['render_controller_param'] as $key => $value) {
            $this->_controller_render_params[$key] = $value;
        }
        unset($GLOBAL['render_controller_param']);
    }
    /**
     * functions defined in Controller Class are disabled for user
     * @return array
     */
    public function getDisabledFunctions() {
        return ;
    }
    /**
     * get paramter value from request parameters
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    final public function getParam($key, $default, $data_type = Datatype::DATA_TYPE_STRING) {
        // get from _GET OR _POST
        $val = $this->_param->get($key, $default, $data_type);
        if (empty($val) &&
            is_array($this->_controller_render_params) &&
            array_key_exists($key, $this->_controller_render_params)) {
            // get from controller rendering params
            $val = $this->_controller_render_params[$key];
        }
        return $val;
    }
    /**
     * assign parameter to view
     * @param string $key
     * @param mixed $val
     */
    final protected function setViewParam($key, $value) {
        $this->_view_param[$key] = $value;
    }
    /**
     * get view parameter
     * @param string $key
     * @param mixed $val
     */
    final protected function getViewParam($key) {
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
    final protected function removeViewParam($key) {
        if (!array_key_exists($key, $this->_view_param)) {
            return ;
        }
        unset($this->_view_param[$key]);
    }
    /**
     * set view type
     * @param string $view_type
     */
    final protected function setViewType($view_type) {
        $this->_view_type = $view_type;
        // message
        __add_info(sprintf('view type has been set to: %s', $view_type),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
    }
    /**
     * get view type
     */
    final protected function getViewType() {
        return $this->_view_type;
    }
    /**
     * set view
     * @throws ViewException
     */
    final protected function setView($view_type) {
        switch ($view_type) {
            case ViewInterface::VIEW_TYPE_SMARTY:
                $this->_view = ClassLoader::loadClass('\view\SmartyView');  break;
            case ViewInterface::VIEW_TYPE_RAIN:
                $this->_view = ClassLoader::loadClass('\view\RainView');  break;
            case ViewInterface::VIEW_TYPE_JSON:
                $this->_view = ClassLoader::loadClass('\view\JsonView');  break;
            case ViewInterface::VIEW_TYPE_TEXT:
                $this->_view = ClassLoader::loadClass('\view\TextView');  break;
            default :
                throw new ViewException('view type not support: ' . $view_type,
                    ExceptionCode::VIEW_TYPE_NOT_SUPPORT);
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
    protected function getViewConfig() {
        $view_config = Application::getConfigByKey('view');
        if (empty($view_config)) {
            $view_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'view.php';
            if (!is_file($view_config)) {
                throw new ViewException(
                    'config file not exist: ' . $view_config,
                    ExceptionCode::VIEW_CONFIG_FILE_NOT_FOUND);
            }
            $view_config = include($view_config);
            Application::setConfig('view', $view_config);
        }
        return $view_config;
    }
    /**
     * render
     */
    final public function render($module, $controller_class, $action_method) {
        if (empty($this->_view)) {
            $this->setView($this->_view_type);
            if (empty($this->_view)) {
                throw new ViewException('set view failed', ExceptionCode::VIEW_EMPTY);
            }
            $this->_view->init($this->getViewConfig());
        }
        if (empty($this->_view_file_path) && in_array($this->_view_type,
            array(ViewInterface::VIEW_TYPE_RAIN, ViewInterface::VIEW_TYPE_SMARTY))) {
            $this->_view_file_path = $this->getViewFilePath($module, $controller_class, $action_method);
        }
        // output contents
        $output = $this->_view->render($this->_view_file_path, $this->_view_param, $this->_cache_param);
        // output encode
        $output_encode = $this->_view->getOutputEncode();
        // enable compress ?
        $is_compress = defined('APPLICATION_COMPRESS_ENABLED') && APPLICATION_COMPRESS_ENABLED;
        // default compress level
        $compress_level = 5;
        switch ($this->_view_type) {
            case ViewInterface::VIEW_TYPE_SMARTY:
            case ViewInterface::VIEW_TYPE_RAIN:
                send_http_response('html', $output, $output_encode, $is_compress, $compress_level);
                break;
            case ViewInterface::VIEW_TYPE_TEXT:
                send_http_response('text', $output, $output_encode, $is_compress, $compress_level);
                break;
            case ViewInterface::VIEW_TYPE_JSON:
                send_http_response('json', $output, $output_encode, $is_compress, $compress_level);
                break;
            default :
                throw new ViewException('view type not support: ' . $this->_view_type,
                    ExceptionCode::VIEW_TYPE_NOT_SUPPORT);
        }
    }
    /**
     * if use smarty or rain view, we need template file
     * user\controller\QueryController::search() -> user/query/search.tpl by default
     * @param  string $module
     * @param  string $controller_class
     * @param  string $action_method
     * @return string
     */
    protected function getViewFilePath($module, $controller_class, $action_method) {
        $controller_class = strtolower(substr($controller_class, strrpos($controller_class, '\\') + 1, -10));
        return "{$module}/{$controller_class}/{$action_method}.tpl";
    }
    /**
     * render controller
     * @param string $controller_class
     * @param string $action_method
     * @param array | null $param
     */
    final public function renderController($controller_class, $action_method, array $param = null) {
        global $GLOBAL;
        $GLOBAL['controller_class'] = $controller_class;
        $GLOBAL['action_method']= $action_method;
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
    final protected function redirect($url, $params = null) {
        $url = add_get_params_to_url($url, $params);
        // message
        __add_info(sprintf('redirect to: %s', $url), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        header('Location:' . $url);
    }
    /**
     * render to error page when exception occurs
     * default: find ErrorController under the same directory
     * @param \Exception $e
     */
    final public function renderError($e) {
        $class_name = get_class($this);
        $module_name = ucfirst(substr($class_name, 0, strpos($class_name, '\\')));
        $error_controller = preg_replace('#\w+$#', $module_name . 'ErrorController', $class_name);
        $this->renderController($error_controller, 'handle', array('render_error_exception' => $e));
    }
    /**
     * __call
     * @param string $name
     * @param array $arguments
     */
    final public function __call($name, $arguments) {
        $name = $this->convertMethodName($name);
        if (!method_exists($this, $name)) {
            send_http_response('forbidden');
            return ;
        }
        $this->$name($arguments);
    }
    /**
     * __destruct
     */
    public function __destruct() {
        $this->clearControllerVariables();
    }
    /**
     * [__clone description]
     */
    public function __clone() {
        $this->clearControllerVariables();
    }
    /**
     * convert method name to method name in use
     *  ax_bx_cx -> aBxCx
     */
    private function convertMethodName($method_name) {
        if (!preg_match('#^([a-z]+_)+[a-z]+$#', $method_name)) {
            return $method_name;
        }
        $method_name_word_list = explode('_', $method_name);
        foreach($method_name_word_list as $key => $method_name_word) {
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
    private function clearControllerVariables() {
        $this->_cache_param = null;
        $this->_controller_render_params = null;
        $this->_param = null;
        $this->_view = null;
        $this->_view_file_path = null;
        $this->_view_param = null;
        $this->_view_type = null;
    }

    /**
     * check if request is ajax
     */
    protected function isAjaxRequest() {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        return false;
    }

    /**
     * prefilter
     */
    public function preFilter() {}
    /**
     * postfilter
     */
    public function postFilter() {}
    /**
     * preRender
     */
    public function preRender() {}
    /**
     * postRender
     */
    public function postRender() {}

}
