<?php
namespace resource;

/**
 * Resource Pool
 * =======================================================
 * can get resource from resource pool
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class ResourcePool {

    protected static $_instance;
    protected $_resources;

    /**
     * constructor
     */
    protected function __construct(){
        $this->_resources = array();
    }
    /**
     * singleton
     * @return Context
     */
    public static function getInstance(){
        if(!static::$_instance){
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * register resource
     * @param string $type
     * @param string $key
     * @param mixed $resource
     */
    public function registerResource($type, $key, $resource) {
        $this->_resources[$type][$key] = $resource;
    }
    /**
     * unregister resource
     * @param string $type
     * @param string $key
     */
    public function unregisterResource($type, $key) {

        if (!array_key_exists($type, $this->_resources)) {
            return ;
        }
        if (!array_key_exists($key, $this->_resources[$type])) {
            return ;
        }
        unset($this->_resources[$type][$key]);
    }
    /**
     * check has resource
     * @param string $type
     * @param string $key
     */
    public function hasResource($type, $key) {
        return isset($this->_resources[$type][$key]);
    }
    /**
     * get resource from pool
     * @param string $type
     * @param string $key
     * @return boolean
     */
    public function getResource($type, $key) {
        if (!$this->hasResource($type, $key)) {
            return null;
        }
        return $this->_resources[$type][$key];
    }

    /**
     * get resource key
     * @param string $adapter
     * @param array $config
     * @return string|mixed
     */
    public function getResourceKey($type, array $resource_config) {
        if (empty($resource_config)) {
            return '';
        }
        $key = $type . '_' . serialize($resource_config);
        return str_replace('.', '_', $key);
    }

}