<?php
namespace context;

/**
 * Context
 * =======================================================
 * Application context. Used for storing temporary application data
 * or othter use
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

abstract class Context {

    protected static $_instance;
    private $context;

    /**
     * constructor
     */
    protected function __construct() {
        $this->context = [];
    }
    /**
     * singleton
     * @return Context
     */
    public static function getInstance() {
        if (static::$_instance !== null) {
            return static::$_instance;
        }
        static::$_instance = new static();
        return static::$_instance;
    }
    /**
     * get data from application context
     * 1. use as key => value
     *    $this->getData($content_key)
     * 2. use as object
     *    $this->getData(array($obj, $method_name), $params)
     * 3. use as method
     *    $this->getData(array($method_name), $params)
     *
     * HOW TO USE:
     *
     *     $this->getData(array($obj, $method_name), array($param1))
     *     $this->getData(array($obj, $method_name), array($param1, $param2...))
     *     $this->getData(array($method_name), array($param1, $param2...))
     * @param string|array $content_key
     * @param mixed $content_value
     * @return null if not exist
     */
    public function getData($content_key, $params = null) {

        if (is_string($content_key)) {
            // get as key => value
            return array_key_exists($content_key, $this->context) ? $this->context[$content_key] : null;
        } elseif (is_array($content_key)) {
            return $this->_getData($content_key, $params);
        }
        return null;
    }
    /**
     * Get data from application context<br>
     * @param object $callable: object used to get data from
     * @param array $params
     * @param bool $force_update true: force get data from object
     * @return mixed
     */
    protected function _getData($callable, $params, $force_update = false) {

        if (!is_callable($callable, false)) {
            return null;
        }

        $key = $this->getKey($callable, $params);
        if (!array_key_exists($key, $this->context) || $force_update) {
            $this->context[$key] = call_user_func_array($callable, $params);
        }
        return $this->context[$key];
    }
    /**
     * set data to application context
     * @param object $callable
     * @param mixed $params
     * @param mixed $additional_data
     * @return multitype:
     */
    protected function _setData($callable, $params) {

        $content_data = $this->getData($callable, $params, true);
        if (empty($content_data)) {
            $content_data = array();
        }
        $content_key                 = $this->getKey($callable, $params);
        $this->context[$content_key] = $content_data;
        return true;
    }
    /**
     * set data to application context
     * 1. use as key => value
     *     $this->setData($content_key, $content_value)
     * 2. use as object
     *    $this->setData(array($obj, $method_name), $params)
     * 3. use as method
     *    $this->setData(array($method_name), $params)
     * @param string|array $content_key
     * @param mixed $content_value
     * @return boolean
     */
    public function setData($content_key, $content_value) {
        if (is_string($content_key)) {
            // use as key => value type
            $this->context[$content_key] = $content_value;
            return true;
        }
        if (is_array($content_key)) {
            // use as object / method
            return $this->_setData($content_key, $content_value);
        }
        return false;
    }
    /**
     * clear data from application context
     * 1. use as key => value
     * 2. use as object
     * 3. use as method
     * @param string|array $content_key
     * @param mixed $content_value
     * @return void
     */
    public function clearData($content_key, $content_value = null) {
        if (is_string($content_key)) {
            // use as key => value type
            unset($this->context[$content_key]);
            return;
        }
        if (is_array($content_key)) {
            // use as object / method
            $content_key = $this->getKey($content_key, $content_value);
            unset($this->context[$content_key]);
            return;
        }
        return;
    }
    /**
     * clear all data from application context
     * @return void
     */
    public function clearAllData() {
        $this->context = [];
    }
    /**
     * get key for data stored in the application context
     * @param object $callable
     * @param mixed $params
     * @return string
     */
    protected function getKey($callable, $params) {
        if (is_string($callable)) {
            $name = 'global_method';
        } else {
            is_callable($callable, false, $name);
        }
        if ($params) {
            return $name . '_' . serialize($params);
        }
        return $name;
    }
}