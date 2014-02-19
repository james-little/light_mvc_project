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
    protected $allowed_files = 'gif|jpg|jpeg|png|txt|zip|rar|tar|gz|mov|flv|mpg|mpeg|mp4|wmv|avi|mp3|wav|ogg';

    /**
     * Try to Upload the given file returning the filename on success
     *
     * @param array $file $_FILES array element
     * @param string $dir destination directory
     * @param boolean $overwrite existing files of the same name?
     * @param integer $size maximum size allowed (can also be set in php.ini or server config)
     */
    public function file($file, $dir, $overwrite = false, $size = false) {
        // Invalid upload?
        if( ! isset($file['tmp_name'], $file['name'], $file['error'], $file['size']) OR $file['error'] != UPLOAD_ERR_OK) {
            return false;
        }
        // File to large?
        if($size && $size > $file['size']) {
            return false;
        }
        // Create $basename, $filename, $dirname, & $extension variables
        extract(pathinfo($file['name']) + array('extension' => ''));
        // Make the name file system safe
        $filename = sanitize($filename, false);
        // We must have a valid name and file type
        if(empty($filename) || empty($extension)) return false;
        $extension = strtolower($extension);
        // Don't allow just any file!
        if( ! $this->allowed_file($extension)) return false;
        // Make sure we can use the destination directory
        directory_make_usable($dir);
        // Create a unique name if we don't want files overwritten
        $name = $overwrite ? $filename.$ext : $this->unique_filename($dir, $filename, $extension);
        // Move the file to the correct location
        if(move_uploaded_file($file['tmp_name'], $dir . $name)) {
            return $name;
        }
    }


    /**
     * Is the file extension allowed
     *
     * @param string $ext of the file
     * @return boolean
     */
    public function allowed_file($ext) {
        if( ! $this->allowed_files) return true;
        return in_array($ext, explode('|', $this->allowed_files));
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
        while(file_exists($dir . $file . $x . $ext)) {
            $x++;
        }
        return $file . $x . $ext;
    }

}