<?php
namespace core\http;

use Application;
use core\http\Request;
use Datatype;
use String;
use Useragent;

class Parameter {

    protected $_params;
    protected static $instance;
    protected $converted_column;

    /**
     * __constructor
     */
    protected function __construct() {
        fix_server_vars();
        $this->_params          = array_merge($_GET, $_POST);
        $this->converted_column = [];
    }
    /**
     * get instance
     * @return \core\http\Parameter
     */
    public static function getInstance() {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new static();
        return self::$instance;
    }
    /**
     * get value from request paramters
     * @param string $key
     * @param mixed $default
     */
    public function get($key, $default = null, $data_type = Datatype::DATA_TYPE_STRING, $method = null) {
        if ($method) {
            switch ($method) {
            case Request::METHOD_GET:
                if (!array_key_exists($key, $_GET)) {
                    return $default;
                }
                return $this->convertData($_GET[$key], $data_type);
            case Request::METHOD_POST:
                if (!array_key_exists($key, $_POST)) {
                    return $default;
                }
                return $this->convertData($_POST[$key], $data_type);
            default:
                return null;
            }
        }
        if (!array_key_exists($key, $this->_params)) {
            return $default;
        }
        if (!empty($this->converted_column[$key])) {
            return $this->_params[$key];
        }
        $this->_params[$key]          = $this->convertData($this->_params[$key], $data_type);
        $this->converted_column[$key] = 1;
        return $this->_params[$key];
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
     * convert data
     */
    protected function convertData($val, $data_type) {
        $val = String::convert2UTF8($val, Application::getInputEncoding());
        $val = Datatype::convertDatatype($val, $data_type);
        return $val;
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
                } elseif (is_array($val)) {
                    $values = $this->convertEmoji($values[$key], $emoji_instance);
                }
            }
        } elseif (is_string($values)) {
            $values = $emoji_instance->filter($values, 'input');
        }
        return $values;
    }
}
