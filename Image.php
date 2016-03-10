<?php
/**
 * Image
 * =======================================================
 * http://imagine.readthedocs.org/en/latest/usage/introduction.html#resize-images
 * http://imagine.readthedocs.org/en/latest/_static/API/
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
ClassLoader::addScanPath(dirname(FRAMEWORK_ROOT_DIR) . '/imagine/lib/');

use exception\ImageException,
    exception\ExceptionCode,
    Imagine\Image\Box,
    Imagine\Image\Point;

class Image {

    private static $instance;
    private $imagine;
    private $image;
    private $filename;
    private $extension;

    /**
     * __construct
     */
    private function __construct() {
        $this->imagine = ClassLoader::loadClass('\Imagine\Gd\Imagine');
    }
    /**
     * get instance
     * @return Image
     */
    public static function getInstance() {
        if(self::$instance) {
            return self::$instance;
        }
        self::$instance = new static();
        return self::$instance;
    }
    /**
     * open image file
     * @param  string $filename
     */
    public function open($filename) {
        if(!is_file($filename)) {
            throw new ImageException('open image failed', ExceptionCode::IMAGE_OPEN_FAILED);
        }
        try {
            $this->image = $this->imagine->open($filename);
            $file_info = File::getFileInfo($filename);
            $this->extension = $file_info['extension'];
            $this->filename = $file_info['filename'];
        } catch (Exception $e) {
            $this->image = null;
            $this->filename = null;
            $this->extension = null;
            throw $e;
        }
        return $this->image;
    }
    /**
     * resize
     * @param  string $des_dir
     * @param  int $width
     * @param  int $height
     * @param  int $quality
     */
    public function resize($width, $height, $quality = null) {
        if(!$this->image) {
            throw new ImageException('call open() first', ExceptionCode::IMAGE_OBJECT_EMPTY);
        }
        $this->image = $this->image->resize(new Box($width, $height));
        return $this->image;
    }
    /**
     * resize in percentage
     * @param  string $des_dir
     * @param  int $percentage
     * @param  int $quality
     */
    public function resizeInPer($percentage, $quality = null) {
        if(!$this->image) {
            throw new ImageException('call open() first', ExceptionCode::IMAGE_OBJECT_EMPTY);
        }
        $size = $this->getSize();
        $width = intval($size['width'] * ($percentage / 100));
        $height = intval($size['height'] * ($percentage / 100));
        return $this->resize($width, $height, $quality);
    }
    /**
     * rotate image
     * @param  int $angle
     */
    public function rotate($angle) {
        if(!$this->image) {
            throw new ImageException('call open() first', ExceptionCode::IMAGE_OBJECT_EMPTY);
        }
        if(!$angle) {
            return $this->image;
        }
        $this->image = $this->image->rotate($angle);
       return $this->image;
    }
    /**
     * watermarking the image
     * @param  string $des_dir
     * @param  string $watermark_file
     * @param  int    $quality
     * @throws RuntimeException
     */
    public function watermark($watermark_file) {
        if(!$this->image) {
            throw new ImageException('call open() first', ExceptionCode::IMAGE_OBJECT_EMPTY);
        }
        if(!is_file($watermark_file)) {
            throw new ImageException('call open() first', ExceptionCode::IMAGE_WATERMARK_FILE_ERR);
        }
        $size = $this->getSize();
        $watermark = $this->imagine->open($watermark_file);
        $wSize = $watermark->getSize();
        $this->image = $this->image->paste($watermark, new Point(
            $size['width'] - $wSize->getWidth(),
            $size['height'] - $wSize->getHeight()
        ));
        return $this->image;
    }
    /**
     * get size
     * @return array
     */
    public function getSize() {
        if(!$this->image) {
            throw new ImageException('call open() first', ExceptionCode::IMAGE_OBJECT_EMPTY);
        }
        $box = $this->image->getSize();
        return ['width' => $box->getWidth(), 'height' => $box->getHeight()];
    }
    /**
     * save to file
     * @param  string $des_file
     * @param  int $quality
     * @throws RuntimeException
     */
    public function save($des_file, $quality = null) {

        $file_info = File::getFileInfo($des_file);
        if(mkdir_r($file_info['dirname']) === false) {
            throw new ImageException('image destination directory access error', ExceptionCode::IMAGE_DES_DIR_ACCESS_ERR);
        }
        $save_file_config = $this->getSaveFileConfig($quality);
        $this->image->save($des_file, $save_file_config);
    }
    /**
     * get save file config
     * @return array
     */
    private function getSaveFileConfig($quality = null) {

        switch ($this->extension) {
            case 'jpg':
            case 'jpeg':
                // from 0 to 100
                $quality = $quality === null ? 70 : $quality;
                return ['jpeg_quality' => $quality];
            case 'png':
                // from 0 to 10
                $quality = $quality === null ? 7 : $quality;
                return ['png_compression_level' => $quality];
            default:
                break;
        }
        return [];
    }

}