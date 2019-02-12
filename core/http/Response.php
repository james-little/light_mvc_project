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

class Response
{

    /**
     * Response content
     */
    protected $_contents;
    /**
     * all headers
     * @var array
     */
    protected $_headers;
    private $is_compress;
    private $compress_level;

    private static $instance;

    /**
     * __constructor
     */
    protected function __construct()
    {
        $this->_headers       = [];
        $this->_contents      = '';
        $this->is_compress    = false;
        $this->compress_level = 5;
    }
    /**
     * get instatnce
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
     * get http response contents
     * @return string
     */
    final public function getContents()
    {
        return $this->_contents;
    }
    /**
     * set http response contents
     * @param string $contens
     * @return string
     */
    final public function setContents($contents)
    {
        $this->_contents = $contents;
    }
    /**
     * append http response contents
     * @param string $contens
     * @return string
     */
    final public function appendContents($contents)
    {
        $this->_contents .= $contents;
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
     * set headers
     * @param string $key
     * @param string $header
     * @return void
     */
    final public function setHeader($key, $header)
    {
        $this->_headers[$key] = $header;
    }
    /**
     * remove header
     * @param string $key
     * @return void
     */
    final public function removeHeader($key)
    {
        unset($this->_headers[$key]);
    }
    /**
     * set is_compress
     * @param bool $is_compress
     * @return array
     */
    final public function setIsCompress($is_compress = false)
    {
        $this->is_compress = $is_compress;
    }
    /**
     * get is_compress
     * @return array
     */
    final public function getIsCompress()
    {
        return $this->is_compress;
    }
    /**
     * set compress level
     * @param int $compress_level
     * @return array
     */
    final public function setCompressLevel($compress_level = 5)
    {
        $this->compress_level = $compress_level;
    }
    /**
     * get compress level
     * @return array
     */
    final public function getCompressLevel()
    {
        return $this->compress_level;
    }
    /**
     * send response
     * @return void
     */
    public function send()
    {
        $this->_headers['Content-Length'] = strlen($this->_contents);
        if ($this->is_compress) {
            $this->_headers['Content-Encoding'] = 'gzip';
            $this->_headers['Vary']             = 'Accept-Encoding';
        }
        foreach ($this->_headers as $key => $header_val) {
            header($key, $header_val);
        }
        if ($this->is_compress) {
            $this->_contents = gzcompress($this->_contents, $this->compress_level);
        }
        echo_pro($this->_contents);
    }
}
