<?php
/**
 * This file is the boot script for job worker.
 * boot the script like below:
 * php /path_to_this_script/bootServer.php %runtime_enviornment% %deubgMode% %output_console_file%
 *
 * 1. runtime_enviornment: The working runtime enviornment you want job worker to work on.
 *    In other words, you want job worker to send request data from develop/production.
 * 2. deubgMode: Set debugMode to true if you want to see the working status of job worker. The working
 *    log would be set under /tmp/AdReward/Jobworker directory seperated by day or under /tmp/ with a
 *    name like develop_cross_pocket_jobworker_%date%. The paramter should be like "debugMode_[0|1]".
 * 3. output_console_file: The job worker is set to work in background mode by default, that means no
 *    output would be shown on console. If you want to see console output, set this parameter with a
 *    file name and console output would be set into the file.
 *
 * Example:
 *
 * boot job worker in develop mode,  and set debug mode to on, with a console output file to
 * /tmp/output.log.
 *
 * command:
 *    php /path_to_this_script/bootServer.php develop deubgMode_1 "/tmp/output.log"
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package -
 * @version 1.0
 */

if (empty($argv[1]) || !in_array($argv[1], array('develop', 'production', 'staging'))) {
    echo "the first parameter should be [ develop | production | staging ] to specify the ad reward enviroment\n";
    exit;
}
// set script execute environment
$envi = $argv[1];
defined('APPLICATION_ENVI') ? null : define('APPLICATION_ENVI', $envi);

// specify config file
if (empty($argv[2]) || !file_exists($argv[2])) {
    echo 'config file :' . $argv[2] . ' seems like not exist or can not readable';
    exit;
}
$config_file = $argv[2];

// set whether in debug mode
$is_debug = false;
if (isset($argv[3]) && preg_match('#^debugMode_#', $argv[3])) {
    $is_debug = str_replace('debugMode_', '', $argv[3]);
    if (is_string($is_debug) && strlen($is_debug) > 1) {
        $is_debug = strtolower(trim($is_debug));
        if ($is_debug == 'on') {
            $is_debug = 1;
        } elseif ($is_debug == 'off') {
            $is_debug = 0;
        }
    }
    $is_debug = (bool) $is_debug;
}

// set console output to specified file
$output_file = empty($argv[4]) ? '/dev/null' : $argv[4];

$config = require($config_file);
$command_base = 'nohup php ' . dirname(__FILE__) . '/createServer.php';
foreach ($config['hosts'] as $host_config) {
    $command = $command_base . ' ' . APPLICATION_ENVI;
    if ($is_debug) {
        $command .= ' debugMode_1';
    } else {
        $command .= ' debugMode_0';
    }
    $command .= " '{$host_config['host']}' {$host_config['port']} {$host_config['max_client']}";
    $command .= " > {$output_file} &";
    echo $command . "\n";
    system($command);
}
