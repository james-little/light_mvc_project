<?php

/**
 * For communications between servers
 * =======================================================
 * This class is based on Curl(http://jp2.php.net/curl)
 * and can work on two modes:
 *    . single-handle mode:
 *      Send only one request at one time.
 *
 *    . multi-handle mode:
 *      Similar to multi-thread but not multi-thread actually.
 *      Send some requests at one time.
 *
 * Example:
 *
 *     ■ single-handle mode:
 *     $communicator = new Communicator();
 *     $option_list = array();
 *     $option_list[CURLOPT_USERAGENT] = $user_agent;
 *     $communicator->addOptions($option_list);
 *     $communicator->addUrl('http://www.google.com');
 *     $contents = $communicator->send();
 *
 *     ■ multi-handle mode:
 *     $url_list = array($url1, $url2, $url3, $url4...);
 *     $communicator = new Communicator();
 *     $option_list = array();
 *     $option_list[CURLOPT_USERAGENT] = $user_agent;
 *     $communicator->addOptions($option_list);
 *     $communicator->setIsMultiOn(true);
 *     // if you want to customize the number of requests send per time
 *     $communicator->setMaxRequest(30);
 *
 *     while(count($url_list)) {
 *         $key = key($url_list);
 *         $url = current($url);
 *         if($communicator->addUrl($url)) {
 *             unset($url_list($key));
 *         }else{
 *             $content_list = $communicator->send();
 *         }
 *     }
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

class Communicator {

    protected $_curl;
    // array
    protected $_curl_option_list;
    protected $_timeout = 1;


    /**
     * __construct
     * @param array $config
     */
    public function __construct($config = null) {

        $this->initCurl();
        if ($config) {
            if (isset($config['is_multi_on'])) {
                $this->setIsMultiOn($config['is_multi_on']);
            }
            if (isset($config['timeout']) && $config['timeout']) {
                $this->setTimeout($config['timeout']);
            }
            if (isset($config['send_request_per_time']) && $config['send_request_per_time']) {
                $this->setMaxRequest($config['send_request_per_time']);
            }
        }
    }
    /**
     * initialize curl
     */
    protected function initCurl() {

        $this->_curl = null;
        $this->_curl = new Curl();
        $option_list = array();
        $option_list[CURLOPT_RETURNTRANSFER] = true;
        $option_list[CURLOPT_SSL_VERIFYPEER] = false;
        $option_list[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
        $option_list[CURLOPT_TIMEOUT] = $this->_timeout;
        $option_list[CURLOPT_CONNECTTIMEOUT] = $this->_timeout;
        $this->setOptions($option_list);
    }
    /**
     * __destruct
     */
    public function __destruct() {
        $this->_curl = null;
        $this->_curl_option_list = null;
        $this->_timeout = null;
    }
    /**
     * reset object when been cloned
     */
    public function __clone() {
        $this->_curl = null;
        $this->_curl_option_list = array();
        $this->_timeout = 1;
    }
    /**
     */
    public function __sleep() {
        $this->_curl = null;
    }
    /**
     * setter
     * set multi mode
     * @param bool $is_multi_on
     */
    public function setIsMultiOn($is_multi_on) {
        $this->_curl->setIsMultiOn($is_multi_on);
    }
    /**
     * is_multi_on getter
     * @return bool $is_multi_on
     */
    public function getIsMultiOn() {
        return $this->_curl->getIsMultiOn();
    }
    /**
     * max_request setter
     * @param int $max_request
     */
    public function setMaxRequest($max_request) {
        if (is_numeric($max_request) && $max_request) {
            $this->_curl->setMaxRequest($max_request);
        }
    }
    /**
     * max_request getter
     * @return int $max_request
     */
    public function getMaxRequest() {
        return $this->_curl->getMaxRequest();
    }
    /**
     * timeout getter
     * @return number
     */
    public function getTimeout() {
        return $this->_timeout;
    }
    /**
     * timeout setter
     * @param int $timeout (in seconds)
     */
    public function setTimeout($timeout) {
        $this->_timeout = $timeout;
        $this->addOption(CURLOPT_TIMEOUT, $this->_timeout);
        $this->addOption(CURLOPT_CONNECTTIMEOUT, $this->_timeout);
    }
    /**
     * set options to option_list
     * @param $option_list
     */
    public function setOptions($option_list) {
        if (is_array($option_list) && count($option_list)) {
            $this->_curl_option_list = $option_list;
        }
    }
    /**
     * add option to option_list by key -> value
     * @param int $key
     * @param mixed $value
     */
    public function addOption($key, $value) {
        $this->_curl_option_list[$key] = $value;
    }
    /**
     * add options to option_list by array
     * duplicated keys will be override by the parameter $options_list
     * @param array $options_list
     */
    public function addOptions($options_list) {
        if (is_array($options_list) && count($options_list)) {
            foreach($options_list as $key => $value) {
                $this->_curl_option_list[$key] = $value;
            }
        }
    }
    /**
     * add url to curl
     * @param string $url
     * @param bool $is_by_post
     * @param array $post_var
     * @return int resource_id
     */
    public function addUrl($url, $is_by_post = false, $post_var = null) {
        return $this->_curl->addUrl($url, $this->_curl_option_list, $is_by_post, $post_var);
    }
    /**
     * send request
     * @param bool $with_curl_info
     * @return array (reference: Curl->exec())
     */
    public function send($with_curl_info = false) {
        if (!$this->_curl) {
            return false;
        }
        return $this->_curl->exec($with_curl_info);
    }

}