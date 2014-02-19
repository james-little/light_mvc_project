<?php
namespace core;

use \ClassLoader,
    core\http\Parameter,
    info\InfoCollector,
    exception\ViewException,
    exception\AppException,
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

class Controller {


    protected $_cache_param;

    protected $_param;
    protected $_view;
    protected $_view_type;
    protected $_view_file_path;
    protected $_view_param;

    /**
     * __constructor
     */
    public function __construct() {
        $this->_param = Parameter::getInstance();
        $view_config = $this->getViewConfig();
        $this->setViewType($view_config['type']);
    }
    /**
     * functions defined in Controller Class are disabled for user
     * @return array
     */
    public function getDisabledFunctions() {
        return get_class_methods($this);
    }
    /**
     * get paramter value from request parameters
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam($key, $default) {
        return $this->_param->get($key, $default);
    }
    /**
     * assign parameter to view
     * @param string $key
     * @param mixed $val
     */
    protected function setViewParam($key, $value) {
        $this->_view_param[$key] = $value;
    }
    /**
     * get view parameter
     * @param string $key
     * @param mixed $val
     */
    protected function getViewParam($key) {
        if (array_key_exists($key, $this->_view_param)) {
            return null;
        }
        return $this->_view_param[$key];
    }
    /**
     * set view type
     * @param string $view_type
     */
    protected function setViewType($view_type) {
        $this->_view_type = $view_type;
        // message
        __add_info(__message('view type has been set to %s', array($view_type)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
    }
    /**
     * set view
     * @throws ViewException
     */
    protected function setView($view_type) {
        switch ($view_type) {
            case 'smarty':
                $this->_view = ClassLoader::loadClass('\view\SmartyView');  break;
            case 'rain':
                $this->_view = ClassLoader::loadClass('\view\RainView');  break;
            case 'json':
                $this->_view = ClassLoader::loadClass('\view\JsonView');  break;
            case 'text':
                $this->_view = ClassLoader::loadClass('\view\TextView');  break;
            default :
                throw new ViewException(__message('view type not support: %s', array($view_type)),
                    ExceptionCode::VIEW_TYPE_NOT_SUPPORT);
        }
        if ($this->_view) {
            // message
            __add_info(__message('view created: %s', array($view_type)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
    }
    /**
     * render
     */
    public function render() {
        if (empty($this->_view)) {
            $this->setView($this->_view_type);
            if (empty($this->_view)) {
                throw new ViewException(__message('set view failed'));
            }
            $this->_view->init($this->getViewConfig());
        }
        $this->_view->render($this->_view_file_path, $this->_view_param, $this->_cache_param);
    }
    /**
     * render controller
     * @param string $controller_class
     * @param string $action_method
     * @param array | null $param
     */
    public function renderController($controller_class, $action_method, array $param = null) {
        global $GLOBAL;
        $GLOBAL['controller_class'] = $controller_class;
        $GLOBAL['action_method']= $action_method;
        $GLOBAL['is_controller_render_controller'] = 1;
        if (!empty($param)) {
            foreach ($this->_param as $key => $value) {
                $this->_param->set($key, $value);
            }
        }
    }
    /**
     * redirect
     * @param string $url
     * @param mixed $params
     */
    protected function redirect($url, $params = null) {
        $url = add_get_params_to_url($url, $params);
        // message
        __add_info(__message('redirect to: %s', array($url)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        header('Location:' . $url);
    }
    /**
     * get view config
     * @return Ambigous <\context\mixed, boolean>
     */
    private function getViewConfig() {

        $app_runtime_context = \AppRuntimeContext::getInstance();
        $view_config = $app_runtime_context->getData('view_config');
        if (empty($view_config)) {
            $view_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DIRECTORY_SEPARATOR . 'view.php';
            if (!file_exists($view_config)) {
                throw new ViewException(__message('view config file not found: %s', array($view_config)),
                    ExceptionCode::VIEW_CONFIG_FILE_NOT_FOUND);
            }
            $view_config = include $view_config;
            $app_runtime_context->setData('view_config', $view_config);
        }
        return $view_config;
    }
    /**
     * render to error page when exception occurs
     * default: find ErrorController under the same directory
     * @param AppException $e
     */
    public function renderError() {
        $class_name = get_class($this);
        $error_controller = preg_replace('#\w+$#', 'ErrorController', $class_name);
        if (!class_exists($error_controller)) {
            throw new AppException(__message('error controller not found: %s', array($error_controller)),
                    ExceptionCode::APPLICATION_ENVI_NOT_DEFINED);
        }
        $this->renderController($error_controller, 'index');
    }
    /**
     * __call
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments) {
        $name = $this->convertMethodName($name);
        if (!method_exists($this, $name)) {
            header("HTTP/1.0 403 Forbidden");
            return ;
        }
        $this->$name($arguments);
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
