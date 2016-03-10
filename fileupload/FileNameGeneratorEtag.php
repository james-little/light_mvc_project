<?php
namespace fileupload;

use FileUpload\FileNameGenerator\FileNameGenerator;

class FileNameGeneratorEtag implements FileNameGenerator {

    /**
     * Get file_name
     * @param  string       $source_name
     * @param  string       $type
     * @param  string       $tmp_name
     * @param  integer      $index
     * @param  string       $content_range
     * @param  Pathresolver $pathresolver
     * @param  Filesystem   $filesystem
     * @return string
     */
    public function getFileName($source_name, $type, $tmp_name, $index, $content_range, $pathresolver, $filesystem) {
        $tmp_upload_dir = ini_get('upload_tmp_dir');
        $etag = $this->geteTag($tmp_upload_dir . '/' . $tmp_name);
        if ($etag[0] === null) {
            // error occurs when $etag[0] is null
            $etag[0] = md5($source_name);
        }
        $extension = substr($source_name, strrpos($source_name, '.')+1);
        return($etag[0].".".$extension);
    }
    /**
     * get etag of file
     * @param string $filename
     * @return array
     */
    public function geteTag($filename) {
        if (!is_file($filename)) {
            return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_OPEN_FAILED];
        }
        $fhandler = fopen($filename, 'r');
        $err = error_get_last();
        if ($err != null) {
            return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_OPEN_FAILED];
        }
        $fstat = fstat($fhandler);
        $fsize = $fstat['size'];
        $block_cnt = $this->blockCount($fsize);
        $sha1_buf = [];
        if ($block_cnt <= 1) {
            $sha1_buf[] = 0x16;
            list($sha1_code, $err) = $this->calSha1($fhandler);
            if ($err != null) {
                return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_READ_FAILED];
            }
            fclose($fhandler);
            $sha1_buf = array_merge($sha1_buf, $sha1_code);
        } else {
            $sha1_buf[] = 0x96;
            $sha1_block_buf = [];
            for ($i=0; $i < $block_cnt; $i++) {
                list($sha1_code, $err) = $this->calSha1($fhandler);
                if ($err != null) {
                    return [null, ErrorCode::ERROR_UPLOAD_TMPFILE_READ_FAILED];
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
        return [$etag, null];
    }
    /**
     * pack to array
     * @param mixed $v
     * @param mixed $a
     */
    private function packArray($v, $a) {
        return call_user_func_array('pack', array_merge([$v],(array)$a));
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
            return [null, $err];
        }
        $byte_array = unpack('C*', $sha1_str);
        return [$byte_array, null];
    }
}