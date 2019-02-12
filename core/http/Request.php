<?php
namespace lightmvc\core\http;

/**
 * Copyright 2016 Koketsu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use lightmvc\context\RuntimeContext;
use lightmvc\core\http\Parameter;
use lightmvc\Datatype;
use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;
use lightmvc\Useragent;

class Request
{

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
    private static $request_method;

    private static $request_methods = [
        'POST'   => self::METHOD_POST,
        'GET'    => self::METHOD_GET,
        'PUT'    => self::METHOD_PUT,
        'DELETE' => self::METHOD_DELETE,
    ];

    const METHOD_POST   = 1;
    const METHOD_GET    = 2;
    const METHOD_PUT    = 3;
    const METHOD_DELETE = 4;

    /**
     * __constructor
     */
    protected function __construct()
    {
        if (!$this->checkIsValidRequest()) {
            throw new AppException(
                sprintf('invalid request: %s', $_SERVER['REQUEST_METHOD']),
                ExceptionCode::REQUEST_NOT_SUPPORT
            );
        }
        $this->_param   = Parameter::getInstance();
        $this->_headers = get_all_headers();
    }
    /**
     * get instatnce
     * @param Parameter $parameter
     * @return Request
     */
    final public static function getInstance()
    {
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
    final public function getParam($key, $default, $data_type = Datatype::DATA_TYPE_STRING, $method = null)
    {
        return $this->_param->get($key, $default, $data_type, $method);
    }
    /**
     * get all headers
     * @return array
     */
    final public function getAllHeaders()
    {
        return $this->_headers;
    }
    /**
     * get headers
     * @param string $filter_express: regular express
     * @return array
     */
    final public function getHeaders($filter_express)
    {
        $headers = [];
        foreach ($this->_headers as $name => $val) {
            if (preg_match($filter_express, $name)) {
                $headers[$name] = $val;
            }
        }
        return $headers;
    }
    /**
     * check is valid request
     * @return boolean
     */
    private function checkIsValidRequest()
    {
        return is_null(self::getRequestMethod()) ? false : true;
    }
    /**
     * get request method
     * @return  int
     */
    final public static function getRequestMethod()
    {
        if (self::$request_method !== null) {
            return self::$request_method;
        }
        $request_method = null;
        if (!empty($_SERVER['REQUEST_METHOD']) && isset(self::$request_methods[$_SERVER['REQUEST_METHOD']])) {
            $request_method = self::$request_methods[$_SERVER['REQUEST_METHOD']];
        }
        self::$request_method = $request_method;
        return self::$request_method;
    }
    /**
     * check if request is ajax
     */
    final public static function isAjaxRequest()
    {
        return get_is_ajax_request();
    }
    /**
     * check if request is post
     */
    final public static function isPost()
    {
        return self::$request_method == self::METHOD_POST;
    }
    /**
     * check if request is get
     */
    final public static function isGet()
    {
        return self::$request_method == self::METHOD_GET;
    }
    /**
     * check if request is put
     */
    final public static function isPut()
    {
        return self::$request_method == self::METHOD_PUT;
    }
    /**
     * check if request is delete
     */
    final public static function isDelete()
    {
        return self::$request_method == self::METHOD_DELETE;
    }
    /**
     * get current url path
     * @throws AppException
     */
    public static function getCurrentUrlPath()
    {
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
    protected static function checkUrl($url)
    {
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
    public static function getClientInfo()
    {
        return [
            'ip'         => get_real_ip_list(),
            'user_agent' => Useragent::getRawUserAgent(),
        ];
    }
}
