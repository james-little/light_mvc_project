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
        $this->_resources = [];
    }
    /**
     * singleton
     * @return Context
     */
    public static function getInstance(){
        if(self::$_instance !== null){
            return self::$_instance;
        }
        self::$_instance = new static();
        return self::$_instance;
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
        if(!array_key_exists($type, $this->_resources)) {
            return false;
        }
        if(!array_key_exists($key, $this->_resources[$type])) {
            return false;
        }
        return true;
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
     * @param array $config
     * @return string|mixed
     */
    public function getResourceKey(array $resource_config) {
        if (empty($resource_config)) {
            return '';
        }
        return md5(_serialize($resource_config));
    }

}