<?php
/**
 * Upload
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

class Upload {

    // List of allowed file extensions separated by "|"
    protected $allowed_files = 'gif|jpg|jpeg|png';
//     protected $allowed_files = 'gif|jpg|jpeg|png|txt|zip|rar|tar|gz|mov|flv|mpg|mpeg|mp4|wmv|avi|mp3|wav|ogg';
    /**
     * Try to Upload the given file returning the filename on success
     *
     * @param array $file $_FILES array element
     * @param string $dir destination directory
     * @param integer $size maximum size allowed (can also be set in php.ini or server config)
     */
    public function upload($file, $dir, $size = false) {
        // Invalid upload?
        if(!isset($file['tmp_name'], $file['name'], $file['error'], $file['size']) || $file['error'] != UPLOAD_ERR_OK) {
            return false;
        }
        // File to large?
        if($size && $size > $file['size']) {
            return false;
        }
        // Create $basename, $filename, $dirname, & $extension variables
        extract(array_merge(pathinfo($file['name']), array('extension' => '')));
        // Make the name file system safe
        $filename = sanitize($filename, false);
        // We must have a valid name and file type
        if(empty($filename) || empty($extension)) return false;
        $extension = strtolower($extension);
        // Don't allow just any file!
        if(!$this->allowed_file($extension)) return false;
        // Make sure we can use the destination directory
        directory_make_usable($dir);
        // Create a unique name if we don't want files overwritten
        $tmp_upload_dir = ini_get('upload_tmp_dir');
        $etag = $this->geteTag($tmp_upload_dir . '/' . $file['tmp_name']);
        if ($etag[0] === null) {
            // error occurs when $etag[0] is null
            return false;
        }
        $etag = $etag[1] . '.' . $extension;
        // Move the file to the correct location
        if(move_uploaded_file($file['tmp_name'], $dir . $etag)) {
            return $etag;
        }
        return false;
    }
    /**
     * Is the file extension allowed
     *
     * @param string $ext of the file
     * @return boolean
     */
    private function allowed_file($ext) {
        if(!$this->allowed_files) return true;
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
    public function unique_filename($dir, $file, $ext) {
        // We start at null so a number isn't added unless needed
        $x = null;
        while(is_file($dir . $file . $x . $ext)) {
            $x++;
        }
        return $file . $x . $ext;
    }
    /**
     * pack to array
     * @param mixed $v
     * @param mixed $a
     */
    private function packArray($v, $a) {
        return call_user_func_array('pack', array_merge(array($v),(array)$a));
    }
    /**
     * block count
     * @param int $fsize
     */
    private function blockCount($fsize) {
        return (($fsize + (1 << 22 - 1)) >> 22);
        //         return (($fsize + (BLOCK_SIZE - 1)) >> BLOCK_BITS);
    }
    /**
     * caculate sha1
     * @param handler $fhandler
     */
    private function calSha1($fhandler) {
        $fdata = fread($fhandler, 1 << 22);
        $sha1_str = sha1($fdata, true);
        $err = error_get_last();
        if ($err != null) {
            return array(null, $err);
        }
        $byte_array = unpack('C*', $sha1_str);
        return array($byte_array, null);
    }
    /**
     * get etag of file
     * @param string $filename
     */
    public function geteTag($filename) {
        if (!is_file($filename)) {
            return array(null, ErrorCode::ERROR_UPLOAD_TMPFILE_OPEN_FAILED);
        }
        $fhandler = fopen($filename, 'r');
        $err = error_get_last();
        if ($err != null) {
            return array(null, ErrorCode::ERROR_UPLOAD_TMPFILE_OPEN_FAILED);
        }
        $fstat = fstat($fhandler);
        $fsize = $fstat['size'];
        $block_cnt = $this->blockCount($fsize);
        $sha1_buf = array();
        if ($block_cnt <= 1) {
            $sha1_buf[] = 0x16;
            list($sha1_code, $err) = $this->calSha1($fhandler);
            if ($err != null) {
                return array(null, ErrorCode::ERROR_UPLOAD_TMPFILE_READ_FAILED);
            }
            fclose($fhandler);
            $sha1_buf = array_merge($sha1_buf, $sha1_code);
        } else {
            $sha1_buf[] = 0x96;
            $sha1_block_buf = array();
            for ($i=0; $i < $block_cnt; $i++) {
                list($sha1_code, $err) = $this->calSha1($fhandler);
                if ($err != null) {
                    return array(null, ErrorCode::ERROR_UPLOAD_TMPFILE_READ_FAILED);
                }
                $sha1_block_buf = array_merge($sha1_block_buf, $sha1_code);
            }
            $tmp_data = $this->packArray('C*', $sha1_block_buf);
            $tmp_fhandler = tmpfile();
            fwrite($tmp_fhandler, $tmp_data);
            fseek($tmp_fhandler, 0);
            list($sha1_final, $_err) = $this->calSha1($tmp_fhandler);
            $sha1_buf = array_merge($sha1_buf, $sha1_final);
        }
        $etag = url_safe_base64_encode($this->packArray('C*', $sha1_buf));
        return array($etag, null);
    }
}