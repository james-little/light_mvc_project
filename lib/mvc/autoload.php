<?php

if (!defined('APPLICATION_DIR')) {
    echo 'APPLICATION_DIR not defined';
    exit(0);
}
if (!defined('FRAMEWORK_ROOT_DIR')) {
    echo 'FRAMEWORK_ROOT_DIR not defined';
    exit(0);
}
/**
 * autoload
 */
defined('FRAMEWORK_SPL_AUTOLOAD') ? null : define('FRAMEWORK_SPL_AUTOLOAD', 0);
if (FRAMEWORK_SPL_AUTOLOAD && set_include_path(get_include_path() . PATH_SEPARATOR . FRAMEWORK_ROOT_DIR) !== false) {
    $registeredAutoLoadFunctions = spl_autoload_functions();
    if (!isset($registeredAutoLoadFunctions['spl_autoload'])) {
        spl_autoload_register();
    }
} else {
    require_once FRAMEWORK_ROOT_DIR . DIRECTORY_SEPARATOR . 'ClassLoader.php';
    $autoloader = new ClassLoader();
    $autoloader->setUseIncludePath(true);
    $autoloader->addScanPath(APPLICATION_DIR . 'protected' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR);
    $autoloader->addScanPath(FRAMEWORK_ROOT_DIR);
}
