<?php
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
 *
 * Resource Pool
 * =======================================================
 * can get resource from resource pool
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\resource;

class ResourcePool
{

    protected static $_instance;
    protected $_resources;

    /**
     * constructor
     */
    protected function __construct()
    {
        $this->_resources = [];
    }
    /**
     * singleton
     * @return Context
     */
    public static function getInstance()
    {
        if (self::$_instance !== null) {
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
    public function registerResource($type, $key, $resource)
    {
        $this->_resources[$type][$key] = $resource;
    }
    /**
     * unregister resource
     * @param string $type
     * @param string $key
     */
    public function unregisterResource($type, $key)
    {
        if (!array_key_exists($type, $this->_resources)) {
            return;
        }
        if (!array_key_exists($key, $this->_resources[$type])) {
            return;
        }
        unset($this->_resources[$type][$key]);
    }
    /**
     * check has resource
     * @param string $type
     * @param string $key
     */
    public function hasResource($type, $key)
    {
        if (!array_key_exists($type, $this->_resources)) {
            return false;
        }
        if (!array_key_exists($key, $this->_resources[$type])) {
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
    public function getResource($type, $key)
    {
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
    public function getResourceKey(array $resource_config)
    {
        if (empty($resource_config)) {
            return '';
        }
        return md5(_serialize($resource_config));
    }
}
