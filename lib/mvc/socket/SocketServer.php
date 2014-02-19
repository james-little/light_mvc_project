<?php
namespace socket;

use exception\SocketException;
/**
 * Socket Server.
 * =======================================================
 * This class is created based on http://cyrilmazur.com/2010/03/sockets-server-in-php.html
 * The class supplied basic socket server functions, and events you can put your
 * own business logic.
 *
 * Function:
 *
 *     . Set if you want a server log.
 *     . Set your server log folder.
 *     . Set encode key if you want the message to be secret.
 *     . Send (encoded)messages to socket client
 *     . Receive message from a socket client
 *     . Start server
 *     . onDataReceived event
 *     . onClientConnected event
 *     . onClientDisconnected event
 *
 * Example:
 *
 *   $server = new SocketServer('127.0.0.1', 99999);
 *   // if you want have your message encoded, set crypt key and the client will
 *   // do it automatically
 *   $server->setEncodeCryptKey('abc');
 *   // set the decode crypt key if the message from the socket server is encoded
 *   $server->setDecodeCryptKey('abc');
 *   // set to true if you want a log file
 *   $server->setIsDebug(true);
 *   $server->start();
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package socket
 * @version 1.0
 **/

class SocketServer {

    // The address the socket will be bound to
    protected $_address;
    // The port the socket will be bound to
    protected $_port;
    // The max number of clients authorized
    protected $_max_clients;
    protected $_clients;
    protected $_client_connect_time;

    // The master socket
    protected $_socket;
    protected $_socket_timeout = 500;
    protected $is_debug = false;
    protected $_log_file;

    protected $_encode_crypt_key;
    protected $_decode_crypt_key;

    /**
     * Constructor
     * @param string $address
     * @param int $port
     * @param int $maxClients
     */
    public function __construct($address, $port, $max_clients) {

        $this->_address = $address;
        $this->_port = $port;
        $this->_max_clients = $max_clients;
        $this->_clients = array();
    }
    /*
     */
    public function __destruct() {
        $this->stop();
    }
    /*
     */
    public function __clone() {
        $this->_address = null;
        $this->_port = null;
        $this->_max_clients = null;
        $this->_clients = array();
        $this->_socket = null;
        $this->_socket_timeout = 500;
        $this->is_debug = false;
        $this->_log_file = null;
        $this->_encode_crypt_key = null;
        $this->_decode_crypt_key = null;
    }
    /**
     * set the debug mode
     * @param bool $is_debug
     */
    public function setIsDebug($is_debug) {
        $this->is_debug = $is_debug;
    }
    /**
     * set the log file
     * @param string $log_file
     */
    public function setLogFile($log_file) {
        $this->_log_file = $log_file;
    }
    /**
     * encode crypt key getter
     * @param string $encode_crypt_key
     * @return string
     */
    public function getEncodeCryptKey() {
        return $this->_encode_crypt_key;
    }
    /**
     * encode crypt key setter
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
     * decode crypt key setter
     * @param string $decode_crypt_key
     */
    public function setDecodeCryptKey($decode_crypt_key) {
        $this->_decode_crypt_key = $decode_crypt_key;
    }
    /**
     * set socket timeout
     * @param string $socket_timeout
     */
    public function setSocketTimeout($socket_timeout) {
        if ($socket_timeout) $this->_socket_timeout = $socket_timeout;
    }
    /**
     * get socket timeout
     */
    public function getSocketTimeout() {
        return $this->_socket_timeout;
    }
    /**
     * Start the server
     */
    public function start() {

        if (!extension_loaded('sockets')) {
            die('The sockets extension is not loaded.');
        }
        error_reporting(E_ALL);
        set_time_limit(0);
        // flush all the output directly
        ob_implicit_flush();

        // create master socket
        $this->_socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!$this->_socket) {
            throw new SocketException('Could not create socket');
        }
        // to prevent: address already in use
        $is_set_option = @socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1);
        if(!$is_set_option) {
            throw new SocketException('Could not set up SO_REUSEADDR');
        }
        @socket_set_nonblock($this->_socket);
        // bind socket to port
        $is_bind_socket = @socket_bind($this->_socket, $this->_address, $this->_port);
        if(!$is_bind_socket) {
            throw new SocketException('Could not bind to socket');
        }
        $this->log('------------socket server started --------------- ');
        $this->log('master socket is ' . $this->_socket . ':' . $this->_port);
        // start listening for connections
        $is_setup_listener = socket_listen($this->_socket);
        if(!$is_setup_listener) {
            throw new SocketException('Could not set up socket listener');
        }
        $this->log('Server started on ' . $this->_address . ':' . $this->_port);
        $read = $write = $except = null;
        // infinite loop
        while(true) {

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            // if the master's status changed, it means a new client would like to connect
            // build the array of sockets to select
            $read = array_merge(array($this->_socket), $this->_clients);
            // if no socket has changed its status, continue the loop
            if (socket_select($read, $write, $except, $this->_socket_timeout) < 1) {
                $this->onClientTimeout();
                continue;
            }
            if (!in_array($this->_socket, $read)) {
                // check the message from clients
                $this->listenClients($read);
                continue;
            }
            // attempt to create a new socket
            $socket_client = socket_accept($this->_socket);
            @socket_set_option($socket_client, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec'=> 0));
            // if socket created successfuly, add it to the clients array and write message log
            if (!$socket_client) {
                $this->log('Impossible to connect new client', true);
                sleep(1);
                continue;
            }
            if (count($this->_clients) >= $this->_max_clients) {
                // tell the client that there is not place available and display error message to the log console
                $this->_send($socket_client, 'Max clients reached. Retry later.');
                $this->_disconnect($socket_client);
                $this->log('Impossible to connect new client: maxClients reached');
                continue;
            }
            // if we didn't reach the maximum amount of connected clients
            $this->_clients[] = $socket_client;
            $this->_client_connect_time[$socket_client] = time();
            if (socket_getpeername($socket_client, $ip)) {
                $this->log('New client connected: ' . $socket_client . ' (' . $ip . ')');
            } else {
                $this->log('New client connected: ' . $socket_client);
            }
            $this->onClientConnected($socket_client);
            // check the message from clients
            $this->listenClients($read);
        }
    }

    /**
     * listen to each clients
     */
    private function listenClients(&$read) {

        if (empty($read)) {
            return ;
        }
        // foreach client that is ready to be read
        foreach($read as $client) {
            // we don't read data from the master socket
            if ($client == $this->_socket) {
                $this->log('skip read from master');
                continue;
            }
            $this->log('start read from ' . $client);
            $input = $this->_read($client);
            if ($input === false) {
                if (time() - $this->_client_connect_time[$client] > 180) {
                    $this->_disconnect($client);
                }
                continue;
            }
            if ($input === null) {
                $this->_disconnect($client, false);
                continue;
            }
            if ($input == 'exit') {
                $this->_disconnect($client);
                continue;
            }
            $this->_client_connect_time[$client] = time();
        }
    }

    /**
     * Stop the server: disconnect all the coonected clients, close the master socket
     */
    public function stop() {

        foreach($this->_clients as $client) {
            @socket_shutdown($client, 2);
            @socket_close($client);
        }
        $this->_clients = array();
        if ($this->_socket) {
            @socket_shutdown($this->_socket, 2);
            @socket_close($this->_socket);
        }
        exit(0);
    }

    /**
     * Disconnect a client
     * @param resource $client
     * @return bool
     */
    protected function _disconnect(&$client, $is_close_socket = true) {

        $this->log('Client disconnected: ' . $client);
        // custom method called
        $this->onClientDisconnected($client);
        // unset variable in the clients array
        $key = array_keys($this->_clients, $client);
        if (!empty($key)) {
            unset($this->_clients[$key[0]]);
        }
        if ($is_close_socket) {
            // shutdown socket
            @socket_shutdown($client, 2);
            // close socket
            @socket_close($client);
        }
    }

    /**
     * read data from socket
     * @return string
     */
    protected function _read(&$client) {

        $data = @socket_read($client, 2048, PHP_NORMAL_READ);
        $this->log('data from client:' . $client . ':' . var_export($data, true), true);
        if ($data === false || $data === null) {
            return $data;
        }
        $data = trim($data, chr(0) . chr(10) . "\t\r");
        if ($this->_decode_crypt_key) {
            $data = decode_crypt_value($data, $this->_decode_crypt_key);
        }
        // custom method called
        $this->onDataReceived($client, $data);
        return $data;
    }

    /**
     * Send data to a client
     * @param resource $client
     * @param string $data
     * @return bool
     */
    protected function _send(&$client, $data) {

        if ($this->_encode_crypt_key) {
            $data = get_crypt_value($data, $this->_encode_crypt_key);
        }
        $data = sprintf('%s%s%s', $data, "\n", chr(0));
        $sent = @socket_write($client, $data, strlen($data));
        if($sent === false) {
            $this->log('send data to ' . $client . ':' . $data . ' failed', true);
            return false;
        }
        $this->log('send data to ' . $client . ':' . $data . ' success', true);
        return true;
    }

    /**
     * Method called after a value had been read
     * @abstract
     * @param resource $socket
     * @param string $data
     */
    protected function onDataReceived(&$client, $data) {
        $this->log('onDataReceived: ' . $data, true);
    }

    /**
     * Method called after a new client is connected
     * @param resource $socket
     */
    protected function onClientConnected(&$client) {
        $this->_send($client, 'connected');
    }

    /**
     * Method called after a new client is disconnected
     * @param resource $socket
     */
    protected function onClientDisconnected(&$client) {
    }
    /**
     * Method called when the select() system call on the given arrays of sockets
     * timeout
     */
    protected function onClientTimeout() {
    }
    /**
     * Write log messages to the console
     * @param string $message
     * @param bool $socketError
     */
    public function log($message, $socket_error = false) {
        if ($this->is_debug) {
            $message_str = '[' . date('Y-m-d H:i:s') . '] ' . $message;
            if ($socket_error) {
                $err_no    = socket_last_error();
                $errMsg    = socket_strerror($err_no);
                $message_str .= ' : #' . $err_no . ' ' . $errMsg;
            }
            $message_str .= "\n";
            error_log($message_str, 3, $this->_log_file);
        }
    }
    /**
     * write to console
     * @param string $message
     */
    public function console($message) {
        $message = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        echo $message;
    }

}