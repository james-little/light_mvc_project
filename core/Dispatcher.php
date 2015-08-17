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
    exception\DbException,
    exception\CacheException,
    exception\AppException,
    exception\ExceptionCode,
    exception\ViewException,
    ErrorCode,
    \ClassLoader,
    \Application,
    \Exception;

class Dispatcher {

    protected $_default_module = 'appDefault';
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
            throw new AppException(sprintf('invalid path: %s', $path),
                ExceptionCode::APP_URL_INVALID);
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
        __add_info(sprintf('path parsed. module: %s,controller: %s,action: %s', $module, $controller, $action),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        $controller = ucfirst($controller);
        $GLOBAL['controller_class'] = "\\{$module}\\controller\\{$controller}Controller";
        $GLOBAL['action_method'] = $action;
        // add locale domain
        if (substr(APPLICATION_LANG, 0 ,2) != 'en') {
            $module_locale_dir = APPLICATION_DIR . 'protected' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'locale';
            if (!is_dir($module_locale_dir)) {
                throw new AppException(
                    sprintf('locale dir not exist: %s', $module_locale_dir),
                    ExceptionCode::APP_LOCALE_DIR_NOT_EXIST);
            }
            Application::setLocale(APPLICATION_LANG, $module_locale_dir, $module);
        }
        $max_loop = 20;
        // start dispatch
        __add_info('start dispatch loop', InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        // start buffer
        ob_start();
        while (!empty($GLOBAL['controller_class']) && $max_loop > 0) {
            $controller_class = $GLOBAL['controller_class'];
            $action_method = empty($GLOBAL['action_method']) ? $this->_default_action : $GLOBAL['action_method'];
            __add_info('controller:'.$controller_class.',action:'.$action_method, InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            if (!class_exists($controller_class)) {
                throw new AppException(
                    sprintf('controller not found: %s', $controller_class),
                    ExceptionCode::APP_CONTROLLER_NOT_FOUND);
            }
            $controller = ClassLoader::loadClass($controller_class);
            // clear render controller variables
            $GLOBAL['is_controller_render_controller'] = 0;
            if (!method_exists($controller, $action_method)) {
                throw new AppException(
                    sprintf('method %s not exist in controller %s', $action_method, $controller_class),
                    ExceptionCode::APP_ACTION_NOT_FOUND);
            }
            if (!$this->isMethodCallable($action_method)) {
                throw new AppException(
                    sprintf('method %s not callable in controller %s', $action_method, $controller_class),
                    ExceptionCode::APP_ACTION_NOT_CALLABLE);
            }
            try {
                $controller->preFilter();
                // prefilter
                __add_info('prefilter executed', InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                // check render from controller
                if ($GLOBAL['is_controller_render_controller']) {
                    // clean buffer is controller rendered
                    ob_clean();
                    $max_loop --;
                    continue ;
                }
                $controller->$action_method();
                // action
                __add_info(sprintf('action executed: %s', $action_method), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                // check render from controller
                if ($GLOBAL['is_controller_render_controller']) {
                    // clean buffer is controller rendered
                    ob_clean();
                    $max_loop --;
                    continue ;
                }
                $controller->preRender();
                // preRender
                __add_info('preRender executed', InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                // check render from controller
                if ($GLOBAL['is_controller_render_controller']) {
                    ob_clean();
                    $max_loop --;
                    continue ;
                }
                $controller->render($module, $controller_class, $action_method);
                // render
                __add_info('render executed', InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->postRender();
                // postRender
                __add_info('postRender executed', InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                $controller->postFilter();
                // postFilter
                __add_info('postFilter executed', InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            } catch (Exception $e) {
                __add_info(
                    sprintf('exception occured in dispatcher: %s', $e->getMessage()),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
                // flush all error contents in the buffer
                ob_flush();
                if ($controller instanceof ErrorController) {
                    throw $e;
                } else {
                    $controller->renderError($e);
                }
                $max_loop --;
                continue ;
            }
            // if nothing exception happened, clear dispatch info and finish loop
            $this->clearDispatcherInfo();
        }
        if(!$max_loop) {
            throw new AppException('main loop has reached its max limit',
                    ExceptionCode::APP_MAINLOOP_REACHED_MAXLIMIT);
        }
        $response = ob_get_contents();
        ob_end_clean();
        echo_pro($response);
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
        $disabled_functions = get_class_methods('\core\Controller');
        if (in_array_pro($action, $disabled_functions)) {
            return false;
        }
        // magic method protect
        if (substr($action, 0, 2) == '__') {
            return false;
        }
        return true;
    }

}
