<?php
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
 *
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
 *     $option_list = [];
 *     $option_list[CURLOPT_USERAGENT] = $user_agent;
 *     $communicator->addOptions($option_list);
 *     $communicator->addUrl('http://www.google.com');
 *     $contents = $communicator->send();
 *
 *     ■ multi-handle mode:
 *     $url_list = array($url1, $url2, $url3, $url4...);
 *     $communicator = new Communicator();
 *     $option_list = [];
 *     $option_list[CURLOPT_USERAGENT] = $user_agent;
 *     $communicator->addOptions($option_list);
 *     $communicator->setIsMultiOn(true);
 *     // if you want to customize the number of requests send per time
 *     $communicator->setMaxRequest(30);
 *
 *     while(count($url_list)) {
 *         $key = key($url_list);
 *         $url = current($url_list);
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
namespace lightmvc;

class Communicator
{

    protected $_curl;
    // array
    protected $_curl_option_list;
    protected $_timeout          = 1;
    protected $_is_proxy_enabled = false;
    protected $_proxy_host;
    protected $_proxy_port;
    protected $_proxy_user;
    protected $_proxy_password;

    /**
     * __construct
     * @param array $config
     */
    public function __construct($config = null)
    {
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
    protected function initCurl()
    {
        $this->_curl                         = null;
        $this->_curl                         = new Curl();
        $option_list                         = [];
        $option_list[CURLOPT_RETURNTRANSFER] = true;
        $option_list[CURLOPT_SSL_VERIFYPEER] = false;
        $option_list[CURLOPT_HTTP_VERSION]   = CURL_HTTP_VERSION_1_0;
        $option_list[CURLOPT_TIMEOUT]        = $this->_timeout;
        $option_list[CURLOPT_CONNECTTIMEOUT] = $this->_timeout;
        $this->setOptions($option_list);
    }
    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->_curl             = null;
        $this->_curl_option_list = null;
        $this->_timeout          = null;
        $this->_proxy_host       = null;
        $this->_proxy_port       = null;
        $this->_proxy_user       = null;
        $this->_proxy_password   = null;
    }
    /**
     * reset object when been cloned
     */
    public function __clone()
    {
        $this->_curl             = null;
        $this->_curl_option_list = [];
        $this->_timeout          = 1;
        $this->_proxy_host       = null;
        $this->_proxy_port       = null;
        $this->_proxy_user       = null;
        $this->_proxy_password   = null;
    }
    /**
     */
    public function __sleep()
    {
        $this->_curl = null;
    }
    /**
     * setter
     * set multi mode
     * @param bool $is_multi_on
     */
    public function setIsMultiOn($is_multi_on)
    {
        $this->_curl->setIsMultiOn($is_multi_on);
    }
    /**
     * is_multi_on getter
     * @return bool $is_multi_on
     */
    public function getIsMultiOn()
    {
        return $this->_curl->getIsMultiOn();
    }
    /**
     * max_request setter
     * @param int $max_request
     */
    public function setMaxRequest($max_request)
    {
        if (is_numeric($max_request) && $max_request) {
            $this->_curl->setMaxRequest($max_request);
        }
    }
    /**
     * max_request getter
     * @return int $max_request
     */
    public function getMaxRequest()
    {
        return $this->_curl->getMaxRequest();
    }
    /**
     * timeout getter
     * @return number
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }
    /**
     * timeout setter
     * @param int $timeout (in seconds)
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;
        $this->addOption(CURLOPT_TIMEOUT, $this->_timeout);
        $this->addOption(CURLOPT_CONNECTTIMEOUT, $this->_timeout);
    }
    /**
     * set proxy settings
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     */
    public function setProxy($host, $port = null, $username = null, $password = null)
    {
        if ($host) {
            $this->_proxy_host = $host;
        }
        if ($port) {
            $this->_proxy_port = $port;
        }
        if ($username) {
            $this->_proxy_user = $username;
        }
        if ($password) {
            $this->_proxy_password = $password;
        }
    }
    /**
     * disable proxy
     * @return void
     */
    public function disableProxy()
    {
        $this->_is_proxy_enabled = false;
    }
    /**
     * enable proxy
     * @return void
     */
    public function enableProxy()
    {
        $this->_is_proxy_enabled = true;
    }
    /**
     * add option to option_list by key -> value
     * @param int $key
     * @param mixed $value
     */
    public function addOption($key, $value)
    {
        $this->_curl_option_list[$key] = $value;
    }
    /**
     * add options to option_list by array
     * duplicated keys will be override by the parameter $options_list
     * @param array $options_list
     */
    public function addOptions($options_list)
    {
        if (is_array($options_list) && count($options_list)) {
            foreach ($options_list as $key => $value) {
                $this->_curl_option_list[$key] = $value;
            }
        }
    }
    /**
     * set options to option_list
     * @param $option_list
     */
    public function setOptions($option_list)
    {
        if (is_array($option_list) && count($option_list)) {
            $this->_curl_option_list = $option_list;
        }
    }
    /**
     * add url to curl
     * @param string  $url
     * @param int     $request_type
     * @param array   $data
     * @param boolean $is_json_request
     * @return int resource_id
     */
    public function addUrl($url, $request_type = Curl::REQUSET_POST, $data = null, $is_json_request = false)
    {
        $this->setProxyToOption();
        if ($this->isSsl($url)) {
            $this->setSSLOptions();
        }
        return $this->_curl->addUrl($url, $this->_curl_option_list, $request_type, $data, $is_json_request);
    }
    /**
     * send request
     * @param bool $with_curl_info
     * @return array (reference: Curl->exec())
     */
    public function send($with_curl_info = false)
    {
        if (!$this->_curl) {
            return false;
        }
        return $this->_curl->exec($with_curl_info);
    }
    /**
     * set proxy options
     */
    private function setProxyToOption()
    {
        if (!$this->_is_proxy_enabled) {
            return;
        }
        $this->_curl_option_list[CURLOPT_HTTPPROXYTUNNEL] = 1;
        if ($this->_proxy_host) {
            $this->_curl_option_list[CURLOPT_PROXY] = $this->_proxy_host;
        }
        if ($this->_proxy_port) {
            $this->_curl_option_list[CURLOPT_PROXYPORT] = $this->_proxy_port;
        }
        if ($this->_proxy_user) {
            $this->_curl_option_list[CURLOPT_PROXYUSERPWD] = $this->_proxy_user . ':' . $this->_proxy_password;
        }
    }
    /**
     * check if the url is with ssl
     * @param  string  $url
     * @return boolean
     */
    private function isSsl($url)
    {
        if (empty($url)) {
            return false;
        }
        if (strtolower(substr($url, 0, 5)) == 'https') {
            return true;
        }
        return false;
    }
    /**
     * set ssl options
     */
    private function setSSLOptions()
    {
        $this->_curl_option_list[CURLOPT_SSL_VERIFYPEER] = true;
        $this->_curl_option_list[CURLOPT_SSL_VERIFYHOST] = 2;
        $this->_curl_option_list[CURLOPT_CAINFO]         = FRAMEWORK_ROOT_DIR . 'curl' . DS . 'cacert.pem';
    }
    /**
     * set cookie
     * @param string $cookie
     */
    public function setCookie($cookie)
    {
        if (!is_file($cookie)) {
            return;
        }
        $this->_curl_option_list[CURLOPT_COOKIEFILE] = $cookie;
    }
    /**
     * use cookie
     * @param string $cookie
     */
    public function useCookie($cookie)
    {
        if (!is_file($cookie)) {
            return;
        }
        $this->_curl_option_list[CURLOPT_COOKIEJAR] = $cookie;
    }
    /**
     * clear cookie
     * @param  string $cookie
     */
    public function clearCookie($cookie)
    {
        @unlink($cookie);
    }
}
