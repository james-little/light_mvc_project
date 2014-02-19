<?php

/**
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
 * $curl->addUrl('http://www.google.co.jp', $option_list);
 * $curl->addUrl('http://www.yahoo.co.jp', $option_list);
 * $contents = $curl->exec();
 *
 * ■ multi-handle mode:
 *
 * SAMPLE:
 * $url_list = array($url1, $url2, $url3, $url4...);
 * $curl = new Curl();
 * $curl->setIsMultiOn(true); // set curl to multi handle mode
 * $curl->setMaxRequest(20); // optional. but with a default value 50
 * while(count($url_list)) {
 *     $key = key($url_list);
 *     $url = current($url);
 *     if($curl->addUrl($url, $option_list)) {
 *         unset($url_list[$key]);
 *     }else{
 *         $content_list = $curl->exec();
 *     }
 * }
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class Curl {

    protected $_max_request = 50;
    // multi thread support
    protected $_multi_handle;
    protected $_curl_list = array();
    protected $_default_option_list = array(
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
        CURLOPT_TIMEOUT => 1,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_VERBOSE => false,
        CURLOPT_RETURNTRANSFER => true
    );
    // cookie directory
    protected $_cookie;
    // multi support
    protected $_is_multi_on = false;


    /**
     * __construct
     */
    public function __construct() {
        $this->_cookie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'curl_cookie';
        $this->_default_option_list[CURLOPT_COOKIEJAR] = $this->_cookie;
        $this->_default_option_list[CURLOPT_COOKIEFILE] = $this->_cookie;
    }

    /**
     * setter
     * set multi mode
     * @param unknown_type $is_multi_on
     */
    public function setIsMultiOn($is_multi_on) {
        if (is_bool($is_multi_on) || is_numeric($is_multi_on)) {
            $this->_is_multi_on = (bool)$is_multi_on;
        }
    }
    /**
     * is_multi_on getter
     * @return bool
     */
    public function getIsMultiOn() {
        return $this->_is_multi_on;
    }
    /**
     * max_request setter
     * @param int $max_request
     */
    public function setMaxRequest($max_request) {
        if (is_numeric($max_request) && $max_request) $this->_max_request = $max_request;
    }
    /**
     * max_request getter
     * @return int
     */
    public function getMaxRequest() {
        return $this->_max_request;
    }
    /**
     * add url (not use post nor get to send data)
     * @param string $url
     * @param array $options CURL option
     * @return null / int
     */
    public function addRawUrl($url, $options = array()) {
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
        $resource_id = (int)$curl;
        $this->_curl_list[$resource_id] = $curl;
        $this->setCurlOption($this->_curl_list[$resource_id], array(CURLOPT_URL => $url), true);
        return $resource_id;
    }
    /**
     * add url
     * @param string $url
     * @param array $options CURL option
     * @param bool $is_by_post
     * @param mixed $var
     * @return null / int
     */
    public function addUrl($url, $options = array(), $is_by_post = false, $var = null) {

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
        $resource_id = (int)$curl;
        $this->_curl_list[$resource_id] = $curl;
        if ($is_by_post) {
            $set_curl_post_options = array(
                CURLOPT_URL => $url,
                CURLOPT_POST => true
            );
            if(!empty($var) && is_array($var)) {
                $set_curl_post_options[CURLOPT_POSTFIELDS] = $var;
            }
            $this->setCurlOption($this->_curl_list[$resource_id], $set_curl_post_options, true);
        }else{
            if (!empty($var) && is_array($var)) {
                $url_part_array = parse_url($url);
                $var_tmp = explode('&', $url_part_array['query']);
                $var_tmp = array_merge($var_tmp, $var);
                $url = $url_part_array['scheme'] . '://' . $url_part_array['host'] . $url_part_array['path'];
                if ($var_tmp && count($var_tmp)) {
                    $url .= '?';
                    $url .= http_build_query($var_tmp);
                }
            }
            $this->setCurlOption($this->_curl_list[$resource_id], array(
                CURLOPT_URL => $url,
                CURLOPT_HTTPGET => true
            ), true);
        }
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
    protected function setCurlOption(&$curl, $option_array = array(), $is_exclude_default = false) {

        if (!$curl || !count($option_array)) {
            return false;
        }
        $curl_option = array();
        if(!$is_exclude_default) {
            $curl_option = $this->_default_option_list;
        }
        foreach ($option_array as $option_key => $option_value) {
            $curl_option[$option_key] = $option_value;
        }
        if (isset($option_array[CURLOPT_POSTFIELDS]) && is_array($option_array[CURLOPT_POSTFIELDS])) {
            $curl_option[CURLOPT_POSTFIELDS] = http_build_query($option_array[CURLOPT_POSTFIELDS], '', '&');
        }
        foreach ($curl_option as $option_key => $option_value) {
            @curl_setopt($curl, $option_key, $option_value);
        }
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
    public function exec($with_curl_info = false) {

        if (!count($this->_curl_list)) {
            return false;
        }
        $contents_list = null;
        if($this->_is_multi_on) {
            $this->_multi_handle = null;
            $this->_multi_handle = curl_multi_init();
            foreach ($this->_curl_list as $curl) {
                curl_multi_add_handle($this->_multi_handle, $curl);
            }
            $active = null;
            //execute the handles in multi-handle mode
            do {
                curl_multi_exec($this->_multi_handle, $active);
            } while ($active > 0);

            $succeed_curl_list = array();
            // Now grab the information about the completed requests
            while ($info = curl_multi_info_read($this->_multi_handle)) {
                $curl = $info['handle'];
                $succeed_curl_list[] = (int)$curl;
            }
            foreach ($this->_curl_list as $key => $curl) {
                if (in_array((int)$curl, $succeed_curl_list)) {
                    $contents_list[$key]['contents'] = curl_multi_getcontent($curl);
                    if ($with_curl_info) {
                        $contents_list[$key]['info'] = curl_getinfo($curl);
                        $contents_list[$key]['errno'] = curl_errno($curl);
                    }
                    curl_multi_remove_handle($this->_multi_handle, $curl);
                    unset($this->_curl_list[$key]);
                }
            }
        }else{
            $key = key($this->_curl_list);
            $curl = current($this->_curl_list);
            $contents_list['contents'] = curl_exec($curl);
            if ($with_curl_info) {
                $contents_list['info'] = curl_getinfo($curl);
                $contents_list['errno'] = curl_errno($curl);
            }
            unset($this->_curl_list[$key]);
        }
        return $contents_list;
    }
    /**
     * close all curl and curl multi handle if in multi-handle mode
     */
    protected function close() {

        if (!count($this->_curl_list)) {
            return ;
        }
        foreach ($this->_curl_list as $curl) {
            if ($this->_multi_handle) {
                curl_multi_remove_handle($this->_multi_handle, $curl);
            }
            curl_close($curl);
            $this->_curl_list = array();
        }
        if ($this->_multi_handle) {
            curl_multi_close($this->_multi_handle);
            $this->_multi_handle = null;
        }
    }
    /**
     * __destruct
     */
    public function __destruct() {

        if(file_exists($this->_cookie)) {
            @unlink($this->_cookie);
        }
        $this->close();
    }
    /**
     */
    public function __clone() {
        $this->close();
        $this->_max_request = 50;
        $this->_default_option_list = array(
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true
        );
        // cookie directory
        $this->_cookie = null;
        // multi support
        $this->_is_multi_on = false;
    }
}