<?php
namespace core\http;

use context\RuntimeContext;
use core\http\Parameter;
use Datatype;
use exception\AppException;
use Useragent;

class Request {

    /**
     * _GET and _POST params from server
     * @var Parameter
     */
    protected $_param;
    /**
     * all headers
     * @var array
     */
    protected $_headers;
    private static $current_path;
    // instance
    private static $instance;

    const METHOD_POST = 1;
    const METHOD_GET  = 2;

    /**
     * __constructor
     */
    protected function __construct() {
        $this->_param   = Parameter::getInstance();
        $this->_headers = get_all_headers();
    }
    /**
     * get instatnce
     * @param Parameter $parameter
     * @return Request
     */
    final public static function getInstance() {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new static();
        return self::$instance;
    }
    /**
     * get param from http params
     * @param  string $key
     * @param  mixed $default
     * @param  int $data_type
     * @param  string $method
     * @return string
     */
    final public function getParam($key, $default, $data_type = Datatype::DATA_TYPE_STRING, $method = null) {
        return $this->_param->get($key, $default, $data_type, $method);
    }
    /**
     * get all headers
     * @return array
     */
    final public function getAllHeaders() {
        return $this->_headers;
    }
    /**
     * get headers
     * @param string $filter_express: regular express
     * @return array
     */
    final public function getHeaders($filter_express) {
        $headers = [];
        foreach ($this->_headers as $name => $val) {
            if (preg_match($filter_express, $name)) {
                $headers[$name] = $val;
            }
        }
        return $headers;
    }
    /**
     * check if request is ajax
     */
    public static function isAjaxRequest() {
        return get_is_ajax_request();
    }
    /**
     * get current url path
     * @throws AppException
     */
    public static function getCurrentUrlPath() {
        if (self::$current_path !== null) {
            return self::$current_path;
        }
        if (RuntimeContext::getInstance()->getAppRunmode() == RuntimeContext::MODE_CLI) {
            global $argv;
            $url = empty($argv[1]) ? null : $argv[1];
        } else {
            $url = current_url();
        }
        if (!self::checkUrl($url)) {
            throw new AppException('invalid url: ' . $url, ExceptionCode::APP_URL_INVALID);
        }
        $url_array          = parse_url($url);
        self::$current_path = empty($url_array['path']) ? '' : $url_array['path'];
        return self::$current_path;
    }
    /**
     * check url
     * @param string $url
     * @throws AppException
     */
    protected static function checkUrl($url) {
        if (empty($url)) {
            // throw exception when url empty
            throw new AppException('url empty', ExceptionCode::APP_URL_EMPTY);
        }
        $is_url_ok = validate_url($url);
        if (!$is_url_ok) {
            // throw exception when url invalid
            throw new AppException('invalid url: ', $url, ExceptionCode::APP_URL_INVALID);
        }
        $url_array = parse_url($url);
        if (empty($url_array)) {
            // throw exception when url parse error
            throw new AppException('parse url error', ExceptionCode::APP_URL_PARSE_ERROR);
        }
        return true;
    }
    /**
     * get client info(ip list and raw user agent)
     * @return  array/string ip and raw user agent string
     */
    public static function getClientInfo() {
        return [
            'ip'         => get_real_ip_list(),
            'user_agent' => Useragent::getRawUserAgent(),
        ];
    }
}
