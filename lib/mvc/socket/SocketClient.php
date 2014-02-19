<?php
namespace socket;

use exception\SocketException;

/**
 * Socket client.
 * ==========================================================
 * You can build a socket client with this class by specifying the socket server
 * address and port.
 * The class supplied basic socket client functions, and events you can put your
 * own business logic.
 *
 * Functions:
 *     . Set the timeout time in miniseconds.
 *     . Set if you want a client log.
 *     . Set your log folder.
 *     . Set encode key if you want the message to be secret.
 *     . Send (encoded)messages to a socket server
 *     . Receive (encoded)messages from a socket server
 *     . onDataReceived event
 *     . onServerConnected event
 *     . onBeforeServerDisconnnected event
 *
 * Example:
 *
 *   $client = new SocketClient('127.0.0.1', 99999);
 *   // if you want have your message encoded, set crypt key and the client will
 *   // do it automatically
 *   $client->setEncodeCryptKey('abc');
 *   // set the decode crypt key if the message from the socket server is encoded
 *   $client->setDecodeCryptKey('abc');
 *   // set to true if you want a log file
 *   $client->setIsDebug(true);
 *   $client->send($data);
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package socket
 * @version 1.0
 **/


class SocketClient {

    // The address the socket will be bound to
    protected $_address;
    // The port the socket will be bound to
    protected $_port;
    protected $_socket;
    // timeout (in mini seconds)
    protected $_timeout = 1000;

    protected $_encode_crypt_key;
    protected $_decode_crypt_key;

    private $is_debug = false;
    protected $_log_file;

    /**
     * Constructor
     * @param string $address
     * @param int $port
     */
    public function __construct($address, $port) {
        $this->_address = $address;
        $this->_port = $port;
    }
    /**
     * __destruct
     */
    public function __destruct() {
        $this->_close();
    }
    /**
     * reset everything to default when been cloned
     */
    public function __clone() {
        $this->_address = null;
        $this->_port = null;
        $this->_socket = null;
        $this->_timeout = 1000;
        $this->is_debug = false;
        $this->_log_file = null;
        $this->_encode_crypt_key = null;
        $this->_decode_crypt_key = null;
    }
    /**
     * set timeout in minisecond
     * @param int $timeout
     */
    public function setTimeout($timeout) {
        if ($timeout && is_numeric($timeout)) {
            $this->_timeout = $timeout;
        }
    }
    /**
     * set client address port
     * @param string $address
     * @param int $port
     */
    public function setAddressPort($address, $port) {
        if (!$address || !$port || !is_numeric($port)) {
            return ;
        }
        $this->_address = $address;
        $this->_port = $port;
    }
    /**
     * encode crypt key getter
     * @return string
     */
    public function getEncodeCryptKey() {
        return $this->_encode_crypt_key;
    }
    /**
     * crypt key setter
     * @param string $encode_crypt_key
     */
    public function setEncodeCryptKey($encode_crypt_key) {
        $this->_encode_crypt_key = $encode_crypt_key;
    }
    /**
     * decode crypt key getter
     * @return string
     */
    public function getDecodeCryptKey() {
        return $this->_decode_crypt_key;
    }
    /**
     * @param string $crypt_key
     */
    public function setDecodeCryptKey($decode_crypt_key) {
        $this->_decode_crypt_key = $decode_crypt_key;
    }
    /**
     * set debug mode
     * @param bool $is_debug
     */
    public function setIsDebug($is_debug) {
        $this->is_debug = $is_debug;
    }
    /**
     * Set log file
     * @param string $log_file
     */
    public function setLogFile($log_file) {
        $this->_log_file = $log_file;
    }

    /**
     * Send data to a socket server and write the response to log file
     * @param string $data
     * @param bool $is_blocking
     * @return bool
     */
    public function send($data, $is_blocking = true, &$response = null) {

        $this->_createSocket($is_blocking);
        $is_success = $this->_send($this->_socket, $data, $is_blocking, $response);
        if ($is_success) {
            if ($response) {
                $response = $this->_read($this->_socket);
            }
            $this->log('send data to server:' . $data . ' success', true);
        } else {
            $this->log('send data to server:' . $data . ' failed', true);
        }
        $this->_send($this->_socket, 'exit');
        $this->_close();
        return (bool)$is_success;
    }
    /**
     * Send data to a client
     * @param resource $client
     * @param string $data
     * @return bool
     */
    protected function _send(&$socket, $data) {

        if($this->_encode_crypt_key) {
            $data = get_crypt_value($data, $this->_encode_crypt_key);
        }
        $data = sprintf('%s%s%s', $data, "\n", chr(0));
        $sent = socket_write($socket, $data, strlen($data));
        if($sent === false || $sent < strlen($data)) {
            $this->log('send data to ' . $socket . ':' . $data . ' failed', true);
            return false;
        }
        $this->log('send data to ' . $socket . ':' . $data . ' success', true);
        return true;
    }
    /**
     * read data from socket
     * @return any type
     */
    protected function _read(&$socket) {

        $response = @socket_read($socket, 2048, PHP_NORMAL_READ);
        if ($response === null || $response === false) {
            return $response;
        }
        $response = trim($response, chr(0) . chr(10) . "\t\r");
        if ($this->_decode_crypt_key) {
            $response = decode_crypt_value($response, $this->_decode_crypt_key);
        }
        $this->log('response from server:' . $response, true);
        return $response;
    }

    /**
     * Close the socket
     */
    protected function _close() {
        if ($this->_socket) {
            $this->onBeforeServerDisconnnected($this->_socket);
            @socket_shutdown($this->_socket);
            @socket_close($this->_socket);
        }
    }
    /**
     * Create socket
     */
    protected function _createSocket($is_with_blocking = true) {

        $retry = 3;
        while ($retry) {
            $this->_socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if($this->_socket) {
                break;
            }
            sleep(0.5);
            $retry--;
        }
        if(!$this->_socket) {
            throw new SocketException('Could not create socket');
        }
        socket_set_option(
            $this->_socket,
            SOL_SOCKET,
            SO_RCVTIMEO,
            array('sec' => 0, 'usec' => $this->_timeout)
        );
        $retry = 3;
        $is_success = false;
        while ($retry) {
            $is_success = @socket_connect($this->_socket, $this->_address, $this->_port);
            if($is_success) {
                break;
            }
            sleep(0.5);
            $retry--;
        }
        if(!$is_success) {
            throw new SocketException('Could not create socket connection');
        }
        if ($is_with_blocking) {
            // check response from server
            $response = $this->_read($this->_socket);
            if (!preg_match('#connected#', $response)) {
                throw new SocketException('Could not connect to socket server');
            }
            $this->log('connected to server:' . $response, true);
        }
        $this->onServerConnected($this->_socket);
        if(!$is_with_blocking && $this->_socket) {
            socket_set_nonblock($this->_socket);
        }
    }

    /**
     * Write log messages to the console
     * @param string $message
     * @param bool $socketError
     */
    public function log($message, $socketError = false) {
        if ($this->is_debug) {
            $messageStr = '[' . date('d/m/Y H:i:s') . '] ' . $message;
            if ($socketError) {
                $errNo    = socket_last_error();
                $errMsg    = socket_strerror($errNo);
                $messageStr .= ' : #' . $errNo . ' ' . $errMsg;
            }
            $messageStr .= "\n";
            error_log($messageStr, 3, $this->_log_file);
        }
    }
    /**
     * OnDataReceived Event. To be override if you want to expand your system
     * @param resource socket
     * @param fixed $data
     */
    protected function onDataReceived($socket, $data) {
    }

    /**
     * OnServerConnnected Event. To be override if you want to expand your system
     * @param resource $socket
     */
    protected function onServerConnected($socket) {
    }

    /**
     * OnBeforeServerDisconnnected Event. To be override if you want to expand your system
     * @param resource $socket
     */
    protected function onBeforeServerDisconnnected($socket) {
    }

}