<?php

class ErrorMessage {

    private $messages;
    private static $instance;
    /**
     * __construct
     */
    private function __construct() {
        $this->messages = include __DIR__ . '/error/error_message_mapping.php';
    }
    /**
     * get instance
     * @return ErrorMessage
     */
    public static function getInstance() {
        if(self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }
    /**
     * add message mapping
     * @param array $message_mapping
     *        error_code => error_message
     * @return bool
     */
    public function addMessageMapping($message_mapping) {
        if(!is_array($message_mapping)) {
            return false;
        }
        foreach($message_mapping as $key => $val) {
            $this->messages[$key] = $val;
        }
        return true;
    }
    /**
     * get error message
     * @param  int $error_code
     * @return string
     */
    public function getErrorMessage($error_code) {
        return isset($this->messages[$error_code]) ? $this->messages[$error_code] : '';
    }
}