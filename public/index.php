<?php
use info\InfoRender;

define('DS', DIRECTORY_SEPARATOR);
define('APPLICATION_DIR', dirname(__DIR__) . DS);
define('FRAMEWORK_ROOT_DIR', APPLICATION_DIR . 'lib' . DS . 'mvc' . DS);
define('APPLICATION_DOC_ROOT', APPLICATION_DIR . 'public' . DS);
// autoload
require_once FRAMEWORK_ROOT_DIR . 'autoload.php';
// handle shut down
register_shutdown_function('handle_shut_down');
require_once APPLICATION_DIR . 'protected' . DS . 'TestApplication.php';
$application = TestApplication::getInstance();

// add authentication
//$auth_logic = new AuthLogic();
//$application->setAuth(array(
//    'auth' => array($auth_logic, 'auth'),
//    'success' => array($auth_logic, 'doAuthSuccess'),
//    'failed' => array($auth_logic, 'doAuthFailed')
//));
try {
    $application->run();
} catch (Exception $e) {
    echo __exception_message($e);
}

/**
 * handle shutdown
 */
function handle_shut_down() {
    if(Session::getIsStarted()) {
        Session::closeSession();
    }
    if (!defined('ENABLE_INFO_COLLECT') || ENABLE_INFO_COLLECT === true) {
        global $GLOBALS;
        $info_collector = empty($GLOBALS['information_collector']) ? null : $GLOBALS['information_collector'];
        if($info_collector) {
            // render collected information
            $info_render = new InfoRender($info_collector);
            $info_render->addAdapter(ClassLoader::loadClass('\info\adapter\InfoRenderAdapterException'));
            // add debug adapter when in develop enviornment
            if (AppRuntimeContext::getInstance()->getIsDebugMode()) {
                $info_render->addAdapter(ClassLoader::loadClass('\info\adapter\InfoRenderAdapterDebug'));
            }
            $info_render->render();
        }
    }
}
