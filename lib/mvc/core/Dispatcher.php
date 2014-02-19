<?php

/**
 * Dispatcher
 * =======================================================
 * Dispatcher parse the url into module, controller and action.
 * Then instance a controller and call action methods
 *
 * If the user does not specify an module, dispatcher will use
 * 'defalult' as its module name, and Index will be the default
 * controller name if controller empty. Default action name is
 * index, too.
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace core;

use info\InfoCollector,
    exception\AppException,
    exception\ExceptionCode,
    exception\ViewException,
    Validator;

class Dispatcher {

    protected $_default_module = 'default';
    protected $_default_controller = 'Index';
    protected $_default_action = 'index';

    /**
     * set default module
     * @param string $module
     */
    public function setDefaultModule($module) {
        $this->_default_module = $module;
    }
    /**
     * get default module
     * @return string
     */
    public function getDefaultModule() {
        return $this->_default_module;
    }
    /**
     * set default module
     * @param string $module
     */
    public function setDefaultController($controller) {
        $this->_default_controller = $controller;
    }
    /**
     * get default controller
     * @return string
     */
    public function getDefaultController() {
        return $this->_default_controller;
    }
    /**
     * set default action
     * @param string $action
     */
    public function setDefaultAction($action) {
        $this->_default_action = $action;
    }
    /**
     * get default action
     * @return string
     */
    public function getDefaultAction() {
        return $this->_default_action;
    }

    /**
     * dispatch
     * @param string $path
     * @throws AppException
     */
    public function dispatch($path) {

        $path = trim($path);
        if (!preg_match('#^(/([^/]+))?(/([^/]+))?(/([^/]+))?#', $path, $matches)) {
            throw new AppException(
                __message('invalid path: %s', array($path)),
                ExceptionCode::BUSINESS_URL_INVALID);
        }
        global $GLOBAL;
        // module
        $module = empty($matches[2]) ? '' : $matches[2];
        if (!$module) {
            $module = $this->_default_module;
        }
        // controller
        $controller = empty($matches[4]) ? '' : $matches[4];
        if (!$controller) {
            $controller = $this->_default_controller;
        }
        $action = empty($matches[6]) ? '' : $matches[6];
        // action
        if (!$action) {
            $action = $this->_default_action;
        }
        // message
        __add_info(__message('path parsed. module:%s,controller:%s,action=%s', array($module, $controller, $action)),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        $controller = ucfirst($controller);
        $GLOBAL['controller_class'] = "\\{$module}\\controller\\{$controller}Controller";
        $GLOBAL['action_method'] = $action;
        // add locale domain
        if (defined('APPLICATION_LANG')) {
            $module_locale_dir = APPLICATION_DIR . 'protected' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'locale';
            if (!file_exists($module_locale_dir)) {
                throw new AppException(
                    __message('locale dir not exist: %s',array($module_locale_dir)),
                    ExceptionCode::BUSINESS_ACTION_NOT_FOUND);
            }
            bindtextdomain($module, $module_locale_dir);
            textdomain($module);
            // message
            __add_info(__message('module locale file loaded. module:%s', array($module)),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
        $max_loop = 5;
        while (!empty($GLOBAL['controller_class']) && $max_loop) {
            ob_start();
            $controller_class = $GLOBAL['controller_class'];
            $action_method = empty($GLOBAL['action_method']) ? $this->_default_action : $GLOBAL['action_method'];
            $GLOBAL['is_controller_render_controller'] = 0;
            if (!class_exists($controller_class)) {
                throw new AppException(
                    __message('controller not found: %s', array($controller_class)),
                    ExceptionCode::BUSINESS_CONTROLLER_NOT_FOUND);
            }
            $controller = new $controller_class();
            if (!method_exists($controller, $action_method)) {
                throw new AppException(
                    __message('method %s not exist in controller %s',array($action_method, $controller_class)),
                    ExceptionCode::BUSINESS_ACTION_NOT_FOUND);
            }
            if (!$this->isMethodCallable($action_method)) {
                throw new AppException(
                    __message('method %s not callable in controller %s',array($action_method, $controller_class)),
                    ExceptionCode::BUSINESS_ACTION_NOT_CALLABLE);
            }
            // message
            __add_info(__message('start dispatch'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            try {
                $controller->preFilter();
                // message
                __add_info(__message('prefilter executed'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->$action_method();
                // message
                __add_info(__message('action executed:%s', array($action_method)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->preRender();
                // message
                __add_info(__message('preRender executed'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->render();
                // message
                __add_info(__message('render executed'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->postRender();
                // message
                __add_info(__message('postRender executed'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->postFilter();
                // message
                __add_info(__message('postFilter executed'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            } catch (ViewException $e) {
                __add_info(
                    __message('view exception occured in dispatcher: %s', array($e->getCode())),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->renderError();
            } catch (Exception $e) {
                __add_info(
                    __message('exception occured in dispatcher: %s', array($e->getCode())),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                send_http_response('file_not_found');
            }
            if ($GLOBAL['is_controller_render_controller']) {
                ob_end_clean();
            } else {
                $this->clearDispatcherInfo();
                ob_end_flush();
            }
            $max_loop--;
        }
    }
    /**
     * clear dispatch information
     */
    private function clearDispatcherInfo() {
        global $GLOBAL;
        unset($GLOBAL['controller_class']);
        unset($GLOBAL['action_method']);
    }
    /**
     * get is method callable
     * @param string $action
     */
    protected function isMethodCallable($action) {
        if (empty($action) || !is_string($action)) {
            return false;
        }
        // functions defined in \core\Controller can not be called
        $disabled_functions = \ClassLoader::loadClass('\core\Controller')->getDisabledFunctions();
        if (in_array_pro($action, $disabled_functions)) {
            return false;
        }
        // magic method protect
        if (preg_match('#^__.+$#', $action)) {
            return false;
        }
        return true;
    }

}
