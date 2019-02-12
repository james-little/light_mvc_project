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
 *
 * CURL
 * multi hanle supported
 * =======================================================
 * ■ single-handle mode:
 * the last one will be used even multiple url has been added when
 * curl is set to single handle mode
 *
 * SAMPLE:
 * $curl = new Curl();
 * $curl->addUrl('http://www.google.com', $option_list);
 * $contents = $curl->exec();
 *
 * ■ multi-handle mode:
 *
 * SAMPLE:
 * $url_list = array($url1, $url2, $url3, $url4...);
 * $curl = new Curl();
 * $curl->setIsMultiOn(true); // set curl to multi handle mode
 * $curl->setMaxRequest(20); // optional. but with a default value 50
 * while (count($url_list)) {
 *     $key = key($url_list);
 *     $url = current($url);
 *     if ($curl->addUrl($url, $option_list)) {
 *         unset($url_list[$key]);
 *     } else {
 *         $content_list = $curl->exec();
 *     }
 * }
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

class Curl
{

    const REQUSET_GET    = 1;
    const REQUSET_POST   = 2;
    const REQUSET_PUT    = 3;
    const REQUSET_DELETE = 4;

    protected $_max_request = 50;
    // multi thread support
    protected $_multi_handle;
    protected $_curl_list           = [];
    protected $_default_option_list = [
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_0,
        CURLOPT_TIMEOUT        => 1,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_VERBOSE        => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => 'gzip,deflate,sdch',
    ];
    // multi support
    protected $_is_multi_on = false;
    private $err_mapping;

    private function loadErrMapping()
    {
        if ($this->err_mapping !== null) {
            return;
        }
        $this->err_mapping = require __DIR__ . DS . 'curl' . DS . 'curl_err.php';
    }
    /**
     * setter
     * set multi mode
     * @param unknown_type $is_multi_on
     */
    public function setIsMultiOn($is_multi_on)
    {
        if (is_bool($is_multi_on) || is_numeric($is_multi_on)) {
            $this->_is_multi_on = (bool) $is_multi_on;
        }
    }
    /**
     * is_multi_on getter
     * @return bool
     */
    public function getIsMultiOn()
    {
        return $this->_is_multi_on;
    }
    /**
     * max_request setter
     * @param int $max_request
     */
    public function setMaxRequest($max_request)
    {
        if (is_numeric($max_request) && $max_request) {
            $this->_max_request = $max_request;
        }
    }
    /**
     * max_request getter
     * @return int
     */
    public function getMaxRequest()
    {
        return $this->_max_request;
    }
    /**
     * add url (not use post nor get to send data)
     * @param string $url
     * @param array  $options CURL option
     * @return null / int
     */
    public function addRawUrl($url, $options = [])
    {
        if (!$url) {
            return null;
        }
        if (!$this->_is_multi_on) {
            $this->_max_request = 1;
        }
        if (count($this->_curl_list) >= $this->_max_request) {
            return null;
        }
        $curl = curl_init();
        if ($options && is_array($options)) {
            $this->setCurlOption($curl, $options);
        }
        $resource_id                    = (int) $curl;
        $this->_curl_list[$resource_id] = $curl;
        $this->setCurlOption($this->_curl_list[$resource_id], [CURLOPT_URL => $url], true);
        return $resource_id;
    }
    /**
     * add url
     * @param string  $url
     * @param array   $options CURL option
     * @param int     $request_type
     * @param mixed   $var array|string
     * @param boolean $is_json_request
     * @return null / int
     */
    public function addUrl($url, $options = [], $request_type = self::REQUSET_POST, $var = null, $is_json_request = false)
    {
        if (!$url) {
            return null;
        }
        if (!$this->_is_multi_on) {
            $this->_max_request = 1;
        }
        if (count($this->_curl_list) >= $this->_max_request) {
            return null;
        }
        $curl                           = curl_init();
        $resource_id                    = (int) $curl;
        $this->_curl_list[$resource_id] = $curl;
        switch ($request_type) {
            case self::REQUSET_GET:
                return $this->addUrlGet($url, $var, $options, $resource_id);
            case self::REQUSET_PUT:
                return $this->addUrlPut($url, $var, $options, $resource_id, $is_json_request);
            case self::REQUSET_DELETE:
                return $this->addUrlDelete($url, $options, $resource_id);
        }
        return $this->addUrlPost($url, $var, $options, $resource_id, $is_json_request);
    }
    /**
     * add url by post
     * @param string       $url
     * @param array | null $var  : post data
     * @param array | null $options : post options
     * @param int          $resource_id
     * @param boolean      $is_json_request
     * @return int: resource_id
     */
    private function addUrlPost($url, $var, $options, $resource_id, $is_json_request)
    {
        // POST
        $var = empty($var) ? [] : $var;
        if (is_string($var)) {
            parse_str($var, $var);
        }
        $parsed_url_part_list = simple_parse_url($url);
        $var                  = array_merge($parsed_url_part_list['query_param'], $var);

        $options = emtpy($options) ? [] : $options;
        // options
        $options[CURLOPT_URL]           = $parsed_url_part_list['uri'];
        $options[CURLOPT_CUSTOMREQUEST] = 'POST';
        $options[CURLOPT_POST]          = true;
        $options[CURLOPT_POSTFIELDS]    = $is_json_request ? json_encode($var) : http_build_query($var);
        if ($is_json_request) {
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]);
        }
        $this->setCurlOption($this->_curl_list[$resource_id], $options);
        return $resource_id;
    }
    /**
     * add url by put
     * @param string        $url
     * @param array | null  $var
     * @param array | null  $options
     * @param int           $resource_id
     * @param boolean       $is_json_request
     * @return int: resource_id
     */
    private function addUrlPut($url, $var, $options, $resource_id, $is_json_request)
    {
        // PUT
        $var = empty($var) ? [] : $var;
        if (is_string($var)) {
            parse_str($var, $var);
        }
        $parsed_url_part_list = simple_parse_url($url);
        $var                  = array_merge($parsed_url_part_list['query_param'], $var);

        $options                        = emtpy($options) ? [] : $options;
        $options[CURLOPT_URL]           = $parsed_url_part_list['uri'];
        $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $options[CURLOPT_POSTFIELDS]    = $is_json_request ? json_encode($var) : http_build_query($var);
        if ($is_json_request) {
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]);
        }
        $this->setCurlOption($this->_curl_list[$resource_id], $options);
        return $resource_id;
    }
    /**
     * add url by delete
     * @param string        $url
     * @param array | null  $var
     * @param array | null  $options
     * @param int           $resource_id
     * @return int: resource_id
     */
    private function addUrlDelete($url, $options, $resource_id)
    {
        // DELETE
        $options[CURLOPT_URL]           = $url;
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        $options                        = emtpy($options) ? [] : $options;
        $this->setCurlOption($this->_curl_list[$resource_id], $options);
        return $resource_id;
    }
    /**
     * add url by get
     * @param string        $url
     * @param array | null  $var
     * @param array | null  $options
     * @param int           $resource_id
     * @return int: resource_id
     */
    private function addUrlGet($url, $var, $options, $resource_id)
    {
        // GET
        $var = empty($var) ? '' : $var;
        if (is_string($var)) {
            parse_str($var, $var);
        }
        $url = add_get_params_to_url($url, $var);

        $options[CURLOPT_URL]     = $url;
        $options[CURLOPT_HTTPGET] = true;
        $this->setCurlOption($this->_curl_list[$resource_id], $options);
        return $resource_id;
    }
    /**
     * curl_setopt($curl, CURLOPT_URL, $url);
     * curl_setopt($curl, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; ja; rv:1.9.0.18) Gecko/2010020220 Firefox/3.0.18 (.NET CLR 3.5.30729) FirePHP/0.4');
     * curl_setopt($curl, CURLOPT_PROXY, '124.39.31.202:8080');
     * curl_setopt($curl, CURLOPT_REFERER, 'http://www.eroanime.tv/html/read_more_1026.html');
     * curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
     * set this to make the request into a body ignore request, some server will return 403
     * curl_setopt($curl, CURLOPT_NOBODY, false);
     * // redirect track
     * curl_setopt($curl, CURLOPT_FOLLOWLOCATION , $followLocation);
     * // timeout
     * curl_setopt($curl, CURLOPT_TIMEOUT, $this->_timeout);
     * // max times allow redirect
     * curl_setopt($curl, CURLOPT_MAXREDIRS, $this->_maxRedirs);
     * // detail output setting
     * curl_setopt($curl, CURLOPT_VERBOSE, false);
     * // make the response into text style
     * curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
     * // the response will return without doing anything
     * curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
     * // IE compatible settings
     * curl_setopt($curl, CURLOPT_HTTPHEADER, array(
     *     'Accept: * /*',
     *     'Accept-Language: ja,en-us;q=0.7,en;q=0.3',
     *     "Connection: Keep-Alive"
     * ));
     * // proxy
     * curl_setopt($curl, CURLOPT_PROXY, $proxy);
     * curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
     * //basic authenticate
     * curl_setopt($curl, CURLOPT_USERPWD, "{$this->_basicAuthId}:{$this->_basicAuthPassword}");
     * curl_setopt($curl, CURLOPT_REFERER, $this->_location);
     *
     * $this->_curlOptions[CURLOPT_POSTFIELDS] = $array;
     * $this->_curlOptions[CURLOPT_POST] = $isUsePost ? true : false;
     * // download file
     * $fh = fopen('/path/to/stored/file/example_file.dat', 'w');
     * curl_setopt($ch, CURLOPT_FILE, $fh);
     * curl_setopt($ch, CURLOPT_URL, 'http://example.com/example_file.dat');
     */
    protected function setCurlOption(&$curl, $option_array = [], $is_exclude_default = false)
    {
        if (!$curl || !count($option_array)) {
            return false;
        }
        $curl_options = $is_exclude_default ? [] : $this->_default_option_list;
        $curl_options = $option_array + $curl_options;
        curl_setopt_array($curl, $curl_options);
    }
    /**
     * curl execute
     *
     *   curlからの情報を返す。
     *   例：
     *   url    (string:40) http://chimei-allguide.com/90/index.html
     *   content_type    (string:29) text/html; charset=iso-8859-1
     *   http_code    (int) 404
     *   header_size    (int) 187
     *   request_size    (int) 264
     *   filetime    (int) -1
     *   ssl_verify_result    (int) 0
     *   redirect_count    (int) 0
     *   total_time    (double) 0.016
     *   namelookup_time    (double) 0
     *   connect_time    (double) 0
     *   pretransfer_time    (double) 0
     *   size_upload    (double) 0
     *   size_download    (double) 283
     *   speed_download    (double) 17687
     *   speed_upload    (double) 0
     *   download_content_length    (double) -1
     *   upload_content_length    (double) 0
     *   starttransfer_time    (double) 0.016
     *   redirect_time    (double) 0
     *
     *   single mode:
     *   array(
     *       contents => %curl_response%
     *       info => %curl_info%             // exists only when $with_curl_info is true
     *       errno => %curl_error_code%      // exists only when $with_curl_info is true
     *   )
     *
     *   multi-mode
     *   array(
     *       %curl_resource_id% => array(contents => %curl_response%, info => %curl_info%, errno => %curl_error_code%),
     *       %curl_resource_id% => array(contents => %curl_response%, info => %curl_info%, errno => %curl_error_code%)
     *   )
     */
    public function exec($with_curl_info = false)
    {
        if (!count($this->_curl_list)) {
            return false;
        }
        $contents_list = null;
        if ($with_curl_info) {
            $this->loadErrMapping();
        }
        if ($this->_is_multi_on) {
            return $this->execMulti($with_curl_info);
        }
        return $this->execSingle($with_curl_info);
    }
    /**
     * execute curl with multi-thread mode
     * @param  bool $with_curl_info
     * @return array
     *         key => $contents
     */
    private function execMulti($with_curl_info)
    {
        $contents_list       = [];
        $this->_multi_handle = null;
        $this->_multi_handle = curl_multi_init();
        foreach ($this->_curl_list as $curl) {
            curl_multi_add_handle($this->_multi_handle, $curl);
        }
        $active = null;
        //execute the handles in multi-handle mode
        do {
            curl_multi_exec($this->_multi_handle, $active);
            curl_multi_select($this->_multi_handle, 1.0);
        } while ($active > 0);

        $succeed_curl_list = [];
        // Now grab the information about the completed requests
        while ($info = curl_multi_info_read($this->_multi_handle)) {
            $curl = $info['handle'];
            if ($info['result'] == CURLE_OK) {
                $succeed_curl_list[(int) $curl] = 1;
            }
        }
        foreach ($this->_curl_list as $resource_id => $curl) {
            if (!isset($succeed_curl_list[(int) $curl])) {
                continue;
            }
            $contents             = [];
            $contents['contents'] = curl_multi_getcontent($curl);
            if ($with_curl_info) {
                $contents['info']  = curl_getinfo($curl);
                $contents['errno'] = curl_errno($curl);
                if (isset($this->err_mapping[$contents['errno']])) {
                    $contents['errno'] = $this->err_mapping[$contents['errno']];
                }
            }
            $contents_list[$resource_id] = $contents;
            curl_multi_remove_handle($this->_multi_handle, $curl);
            curl_close($curl);
            unset($this->_curl_list[$resource_id]);
        }
        return $contents_list;
    }
    /**
     * execute curl in single mode
     * @param  bool $with_curl_info
     * @return array
     */
    private function execSingle($with_curl_info)
    {
        $contents_list             = [];
        $key                       = key($this->_curl_list);
        $curl                      = current($this->_curl_list);
        $contents_list['contents'] = curl_exec($curl);
        if ($with_curl_info) {
            $contents_list['info']  = curl_getinfo($curl);
            $contents_list['errno'] = curl_errno($curl);
            if (isset($this->err_mapping[$contents_list['errno']])) {
                $contents_list['errno'] = $this->err_mapping[$contents_list['errno']];
            }
        }
        curl_close($curl);
        unset($this->_curl_list[$key]);
        return $contents_list;
    }
    /**
     * close all curl and curl multi handle if in multi-handle mode
     */
    protected function close()
    {
        if (!count($this->_curl_list)) {
            return;
        }
        foreach ($this->_curl_list as $curl) {
            if ($this->_multi_handle) {
                curl_multi_remove_handle($this->_multi_handle, $curl);
            }
            curl_close($curl);
            $this->_curl_list = [];
        }
        if ($this->_multi_handle) {
            curl_multi_close($this->_multi_handle);
            $this->_multi_handle = null;
        }
    }
    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->close();
    }
    /**
     */
    public function __clone()
    {
        $this->close();
        $this->_max_request         = 50;
        $this->_default_option_list = [
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_0,
            CURLOPT_TIMEOUT        => 1,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_VERBOSE        => false,
            CURLOPT_RETURNTRANSFER => true,
        ];
        // multi support
        $this->_is_multi_on = false;
    }
}
