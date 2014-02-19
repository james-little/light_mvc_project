<?php
use exception\SocketException,
    socket\ResourcePoolSocketServer,
    socket\SocketServer;

if (empty($argv[1]) || !in_array($argv[1], array('develop', 'production', 'staging'))) {
    echo "the first parameter should be [ develop | production | staging ] to specify the ad reward enviroment\n";
    exit;
}
// set script execute environment
define('RUNTIME_ENVI', $argv[1]);

// debug mode
$is_debug = $argv[2];
if (!preg_match('#^debugMode_#', $is_debug)) {
    echo "the second parameter should be start with debugMode_\n";
    exit;
}
$is_debug = str_replace('debugMode_', '', $is_debug);
if (is_string($is_debug) && strlen($is_debug) > 1) {
    $is_debug = strtolower(trim($is_debug));
    if ($is_debug == 'on') {
        $is_debug = 1;
    } elseif ($is_debug == 'off') {
        $is_debug = 0;
    }
}
// host
$server_host = empty($argv[3]) ? '' : $argv[3];
if (!$server_host) {
    echo "the third parameter should be host name\n";
    exit;
}
// port
$server_port = empty($argv[4]) ? 0 : $argv[4];
if (!$server_port) {
    echo "the fourth parameter should be host port\n";
    exit;
}
$server_max_client = empty($argv[5]) ? 500 : $argv[5];
if (!$server_host || !$server_port || !$server_max_client) {
    echo "must be something wrong with the paramaters\n";
    exit;
}
define('DS', DIRECTORY_SEPARATOR);
define('APPLICATION_DIR', dirname(dirname(__DIR__)) . DS);
define('FRAMEWORK_ROOT_DIR', APPLICATION_DIR . 'lib' . DS . 'mvc' . DS);
// autoload
require_once FRAMEWORK_ROOT_DIR . 'autoload.php';
Application::includeFrameworkFunctions();

set_time_limit(0);
$config_dir = dirname(__DIR__) . '/config/';
$server = new ResourcePoolSocketServer($server_host, $server_port, $server_max_client);
$db_config = require ($config_dir . RUNTIME_ENVI . '/database.php');
$server->setResourceConfig('db', $db_config);
$cache_config = require ($config_dir . RUNTIME_ENVI . '/cache.php');
$server->setResourceConfig('cache', $cache_config);
$server->setMaxResourceLimit(500);
$server->setIsDebug($is_debug);
try{
    $server->start();
}catch(SocketException $e) {
    echo "resource pool server failed to start up. message: {$e->getMessage()}\n";
}
