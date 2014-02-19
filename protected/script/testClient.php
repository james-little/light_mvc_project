<?php
use exception\SocketException,
    socket\SocketClient;

$command = "db://type=mysql&table_name=user_account&method_name=queryRow&sql=".urlencode("SELECT * FROM user_account");
$result = callSocketClient("127.0.0.1", 39997, $command, 1);
var_dump($result);
/**
 * use client to send socket command to job worker
 */
function callSocketClient($host, $port, $command, $is_debug) {

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
    $connection = socket_connect($socket, $host, $port);
    read($socket, $is_debug);
    if (!write($socket, $command)) {
        if($is_debug) {
            echo "Write failed\n";
        }
    }
    $response = read($socket, $is_debug);
    write($socket, 'exit');
    @socket_shutdown($socket);
    @socket_close($socket);
    return $response == 'ok' ? true : false;
}

function write($socket, $data) {
    return socket_write($socket, format($data));
}

function read($socket, $is_debug) {
    $buffer = @socket_read($socket, 1024, PHP_NORMAL_READ);
    if($buffer === false || $buffer === null) {
        return $buffer;
    }
    $result = trim($buffer, chr(0) . chr(10) . "\t\r");
    if($is_debug) {
        echo "response from server: {$result}\n";
    }
    return $result;
}

function format($data) {
    return sprintf('%s%s%s', $data, "\n", chr(0));
}