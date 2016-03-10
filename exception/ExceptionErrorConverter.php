<?php

/**
 * ExceptionErrorConverter
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace exception;

class ExceptionErrorConverter {

    private static $instance;
    private static $mapping;

    /**
     * __construct
     */
    protected function __construct() {
        $this->loadDefault();
    }
    /**
     * load framework level exception code <-> error code mapping
     */
    private function loadDefault() {
        self::$mapping = require __DIR__ . '/exception_error_mapping.php';
    }
    /**
     * get instance
     * @return ExceptionErrorConverter
     */
    public static function getInstance() {
        if(self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new static();
        return self::$instance;
    }
    /**
     * add map to mapping
     * @param int $exception_code
     * @param int $error_code
     */
    public function add($exception_code, $error_code) {
        if(isset(self::$mapping[$exception_code])) {
            return ;
        }
        self::$mapping[$exception_code] = $error_code;
    }
    /**
     * merge with specified exception error mapping
     * used for add multiple error mapping at one time
     * the existed key <-> value would be overwrite by
     * the mapping specified.
     */
    public function merge(array $exception_error_mapping) {
        if (empty($exception_error_mapping)) {
            return ;
        }
        foreach ($exception_error_mapping as $key => $val) {
            self::$mapping[$key] = $val;
        }
    }
    /**
     * remove map from mapping
     * @param int $exception_code
     */
    public function remove($exception_code) {
        if(isset(self::$mapping[$exception_code])) {
            unset(self::$mapping[$exception_code]);
        }
    }
    /**
     * get map from mapping
     * @param int $exception_code
     * @return int || null
     */
    public function get($exception_code) {
        if(isset(self::$mapping[$exception_code])) {
            return self::$mapping[$exception_code];
        }
        return null;
    }
}