<?php
namespace core\http;

use Useragent;

class Parameter {

    protected $_params;
    protected $_emoji_parameter_keys = array();
    protected static $instance;

    /**
     * __constructor
     */
    protected function __construct() {

        $this->_params = array_merge($_GET, $_POST);
        // convert encoding
        $this->convertEncode();
    }
    /**
     * get instance
     * @return \core\http\Parameter
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    /**
     * get value from request paramters
     * @param string $key
     * @param mixed $default
     */
    public function get($key, $default = null) {
        if (array_key_exists($key, $this->_params)) {
            return $this->_params[$key];
        }
        return $default;
    }
    /**
     * set value to params
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value) {
        $this->_params[$key] = $value;
    }
    /**
     * get all values from request
     * @return multitype:
     */
    public function getAll() {
        return $this->_params;
    }

    /**
     * set emoji keys(for japan only)
     * @param array $keys
     */
    public function setEmojiKeys(array $keys) {
        $this->_emoji_parameter_keys = $keys;
    }
    /**
     * check if value contains emoji
     * @param stirng $key
     * @return boolean
     */
    public function emojiExists($key) {
        if (!array_key_exists($key, $this->_params)) {
            return false;
        }
        $regexp =
            "/(?:\xee(?:(?:[\x81-\x93\x99-\x9c\xb1-\xff][\x80-\xbf])|" .
            "(?:\x80[\x81-\xbf])|(?:\x94[\x80-\xbe])|" .
            "(?:\x98[\xbe-\xbf])|(?:\x9d[\x80-\x97])))|" .
            "(?:\xef(?:(?:[\x80-\x82][\x80-\xbf])|(?:\x83[\x80-\xbc])))/";
        return preg_match($regexp, $this->_params[$key]);
    }
    /**
     * convert encoding
     */
    protected function convertEncode() {

        foreach ($this->_params as $key => $value) {
            if (!empty($this->_emoji_parameter_keys) && in_array_pro($key, $this->_emoji_parameter_keys)) {
                $this->_params[$key] = $this->convertEmoji($value, $emoji_instance);
            } else {
                $this->_params[$key] = convert2utf8($value);
            }
        }
    }
    /**
     * convert emoji
     * @param string $values
     * @param mixed $emoji_instance
     * @return mixed
     */
    protected function convertEmoji($values, $emoji_instance = null) {

        if (!$emoji_instance) {
            require 'HTML/Emoji.php';
            $emoji_instance = new \HTML_Emoji(Useragent::getCarrier());
        }
        if (is_array($values)) {
            foreach ($values as $key => $val) {
                if (is_string($val)) {
                    $values[$key] = $emoji_instance->filter($val, 'input');
                } else if (is_array($val)) {
                    $values = $this->convertEmoji($values[$key], $emoji_instance);
                }
            }
        } else if (is_string($values)) {
            $values = $emoji_instance->filter($values, 'input');
        }
        return $values;
    }
}
