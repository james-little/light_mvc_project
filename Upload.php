<?php
/**
 *  Copyright 2016 Koketsu
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
 * ==============================================================================
 * Upload
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

class Upload
{

    /** List of allowed file extensions separated by "|"
     * suuported files:
     * gif|jpg|jpeg|png|txt|zip|rar|tar|gz|mov|flv|mpg|mpeg|mp4|wmv|avi|mp3|wav|ogg
     */
    private $allowed_files;
    // set default max size to 8MB
    private $max_size;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->allowed_files = 'gif|jpg|jpeg|png';
        // set default max size to 8MB
        $this->max_size = 8 * 1024 * 1024;
    }

    /**
     * set upload max size
     * @param int $max_size [description]
     */
    public function setMaxSize($max_size)
    {
        if (!$max_size) {
            return $this;
        }
        $this->max_size = $max_size;
        return $this;
    }
    /**
     * set allowed files
     * @param string $allow_files
     */
    public function setAllowedFiles($allow_files)
    {
        if (!$allow_files) {
            return $allow_files;
        }
        $this->allowed_files = $allow_files;
        return $this;
    }
    /**
     * get upload files information
     * this function would format the upload file info from:
     * array(
     *     'name' => array('0' => 'a.txt', '1' => 'b.txt'),
     *     'type' => array('0' => 'text/plain', '1' => 'text/plain'),
     *     'tmp_name' => array('0' => '/tmp/phpYzdqkD', '1' => '/tmp/phpeEwEWG'),
     *     'error' => array('0' => 0, '1' => 0),
     *     'size' => array('0' => 123, '1' => 456),
     * )
     *
     * into :
     * array(
     *     '0' => array(
     *         'name' => 'a.txt',
     *         'type' => 'text/plain',
     *         'tmp_name' => '/tmp/phpYzdqkD',
     *         'error' => 0,
     *         'size' => 123,
     *     ),
     *     '1' => array(
     *         'name' => 'b.txt',
     *         'type' => 'text/plain',
     *         'tmp_name' => '/tmp/phpeEwEWG',
     *         'error' => 0,
     *         'size' => 456,
     *     ),
     * )
     * @param string $name : file element name in the HTML
     * @return array
     */
    public static function getUploadFiles($name)
    {
        if (empty($name) || !isset($_FILES[$name])) {
            return [];
        }
        $len          = count($_FILES[$name]['name']);
        $upload_files = [];
        for ($i = 0; $i < $len; $i++) {
            foreach ($_FILES[$name] as $key => $val) {
                $upload_files[$i][$key] = $val[$i];
            }
        }
        return $upload_files;
    }

    /**
     * Try to Upload the given file returning the filename on success
     *
     * @param array $file $_FILES array element
     * @param string $dir destination directory
     * @param integer $size maximum size allowed (can also be set in php.ini or server config)
     */
    public function upload($file, $des_dir, $custom_name = null, $is_override = true)
    {
        // Invalid upload?
        if (!isset($file['tmp_name'], $file['name'], $file['error'], $file['size'])) {
            return ['success' => false, 'filename' => ''];
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'filename' => ''];
        }
        if ($file['error'] != UPLOAD_ERR_OK) {
            return ['success' => false, 'filename' => ''];
        }
        $tmp_upload_dir = ini_get('upload_tmp_dir');
        if (filesize($tmp_upload_dir . '/' . $file['tmp_name']) != $file['size']) {
            return ['success' => false, 'filename' => ''];
        }
        // File to large?
        if ($this->max_size < $file['size']) {
            return ['success' => false, 'filename' => ''];
        }
        // Create $basename, $filename, $dirname, & $extension variables
        extract(array_merge(['extension' => ''], mb_pathinfo($file['name'])));
        // We must have a valid name and file type
        if (empty($filename) || empty($extension)) {
            return ['success' => false, 'filename' => ''];
        }
        $extension = strtolower($extension);
        // Don't allow just any file!
        if (!$this->allowedFile($extension)) {
            return ['success' => false, 'filename' => ''];
        }
        // Make sure we can use the destination directory
        if (mkdir_r($des_dir) === false) {
            return ['success' => false, 'filename' => ''];
        }
        $des_file_name = '';
        if ($custom_name) {
            $des_file_name = "{$des_dir}/{$custom_name}.{$extension}";
        } else {
            // Create a unique name if we don't want files overwritten
            $etag = $this->geteTag($tmp_upload_dir . '/' . $file['tmp_name']);
            if ($etag[0] === null) {
                // error occurs when $etag[0] is null
                return ['success' => false, 'filename' => ''];
            }
            $des_file_name = "{$des_dir}/{$etag[0]}.{$extension}";
        }
        if (!$is_override && is_file($des_file_name)) {
            @unlink($file['tmp_name']);
            return ['success' => true, 'filename' => $des_file_name];
        }
        // Move the file to the correct location
        if (move_uploaded_file($file['tmp_name'], $des_file_name)) {
            @chmod($des_file_name, 0777);
            return ['success' => true, 'filename' => $des_file_name];
        }
        return ['success' => false, 'filename' => ''];
    }
    /**
     * Is the file extension allowed
     *
     * @param string $ext of the file
     * @return boolean
     */
    private function allowedFile($ext)
    {
        if (!$this->allowed_files) {
            return true;
        }

        return preg_match("/\|?{$ext}\|?/", $this->allowed_files);
    }
    /**
     * Create a unique filename by appending a number to the end of the file
     *
     * @param string $dir to check
     * @param string $file name to check
     * @param string $ext of the file
     * @return string
     */
    public function uniqueFilename($dir, $file, $ext)
    {
        // We start at null so a number isn't added unless needed
        $x = null;
        while (is_file($dir . $file . $x . $ext)) {
            $x++;
        }
        return $file . $x . $ext;
    }
    /**
     * pack to array
     * @param mixed $v
     * @param mixed $a
     */
    private function packArray($v, $a)
    {
        return call_user_func_array('pack', array_merge([$v], (array) $a));
    }
    /**
     * block count
     * @param int $fsize
     */
    private function blockCount($fsize)
    {
        return (($fsize + (1 << 22 - 1)) >> 22);
        //         return (($fsize + (BLOCK_SIZE - 1)) >> BLOCK_BITS);
    }
    /**
     * caculate sha1
     * @param handler $fhandler
     */
    private function calSha1($fhandler)
    {
        $fdata    = fread($fhandler, 1 << 22);
        $sha1_str = sha1($fdata, true);
        $err      = error_get_last();
        if ($err != null) {
            return [null, $err];
        }
        $byte_array = unpack('C*', $sha1_str);
        return [$byte_array, null];
    }
    /**
     * get etag of file
     * @param string $filename
     */
    public function geteTag($filename)
    {
        if (!is_file($filename)) {
            return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_OPEN_FAILED];
        }
        $fhandler = fopen($filename, 'r');
        $err      = error_get_last();
        if ($err != null) {
            return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_OPEN_FAILED];
        }
        $fstat     = fstat($fhandler);
        $fsize     = $fstat['size'];
        $block_cnt = $this->blockCount($fsize);
        $sha1_buf  = [];
        if ($block_cnt <= 1) {
            $sha1_buf[]            = 0x16;
            list($sha1_code, $err) = $this->calSha1($fhandler);
            if ($err != null) {
                return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_READ_FAILED];
            }
            fclose($fhandler);
            $sha1_buf = array_merge($sha1_buf, $sha1_code);
        } else {
            $sha1_buf[]     = 0x96;
            $sha1_block_buf = [];
            for ($i = 0; $i < $block_cnt; $i++) {
                list($sha1_code, $err) = $this->calSha1($fhandler);
                if ($err != null) {
                    return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_READ_FAILED];
                }
                $sha1_block_buf = array_merge($sha1_block_buf, $sha1_code);
            }
            $tmp_data     = $this->packArray('C*', $sha1_block_buf);
            $tmp_fhandler = tmpfile();
            fwrite($tmp_fhandler, $tmp_data);
            fseek($tmp_fhandler, 0);
            list($sha1_final, $_err) = $this->calSha1($tmp_fhandler);
            $sha1_buf                = array_merge($sha1_buf, $sha1_final);
        }
        $etag = url_safe_base64_encode($this->packArray('C*', $sha1_buf));
        return [$etag, null];
    }
}
