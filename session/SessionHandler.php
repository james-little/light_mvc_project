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
 * Session Handler
 * @author koketsu<jameslittle.private@gmail.com>
 * @version 1.0
 */
namespace lightmvc\session;

use lightmvc\ClassLoader;
use lightmvc\info\InfoCollector;
use lightmvc\resource\cache\Cache;
use lightmvc\Session;

class SessionHandler
{

    private $model;
    private $lifetime;
    private static $instance;
    private $cache;

    /**
     * constructor
     */
    private function __construct($model_class = null)
    {
        if ($model_class) {
            $this->model = ClassLoader::loadClass($model_class);
        }
        $this->cache    = Cache::getInstance();
        $this->lifetime = ini_get('session.gc_maxlifetime');
    }
    /**
     * get instance of handler
     */
    public static function getInstance($model_class = null)
    {
        if (static::$instance !== null) {
            return static::$instance;
        }
        static::$instance = new static($model_class);
        return static::$instance;
    }
    /**
     * open session
     * @param string $path
     * @param string $name
     * @return boolean
     */
    public function open($path, $name)
    {
        __add_info(
            'session opened: ' . session_id(),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return true;
    }
    /**
     * close session
     * @return boolean
     */
    public function close()
    {
        __add_info(
            'session closed: ' . session_id(),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return true;
    }
    /**
     * destroy session data
     * @param string $session_id
     * @return boolean
     */
    public function destroy($session_id)
    {
        __add_info(
            'session destroyed: ' . session_id(),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        $affect_rows = $this->destroySessionDataToDb($session_id);
        $this->clearSessionDataCache($session_id);
        return $affect_rows > 0;
    }
    /**
     * recycle session data
     * @param int $lifetime
     * @return boolean
     */
    public function gc($lifetime)
    {
        $this->recycleSessionDataToDb();
        return true;
    }
    /**
     * read session data
     * @param string $session_id
     * @return string|Ambigous <>
     */
    public function read($session_id)
    {
        __add_info(
            sprintf('read session[%s]: %s', Session::$is_new_session ? 'new' : 'existed', $session_id),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        if (Session::$is_new_session) {
            return '';
        }

        if (Session::$is_change_timely && Session::$old_session_id) {
            $this->updateOldSessionIdWithNew(Session::$old_session_id, $session_id);
        }
        $value = $this->getSessionDataFromCache($session_id);
        if ($value === null) {
            $value = $this->getSessionDataFromDb($session_id);
        }
        $_SESSION = $this->decodeSessionData($value);
        if (isset($_SESSION) && !empty($_SESSION) && $_SESSION != null) {
            return session_encode();
        }
        return '';
    }
    /**
     * write session data
     * @param string $session_id
     * @param string $session_data_value
     * @return boolean
     */
    public function write($session_id, $session_data)
    {
        __add_info(
            'write session: ' . $session_id,
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        $uid = isset($_SESSION['uid']) ? $_SESSION['uid'] : 0;
        if (Session::$is_new_session) {
            // write session data to database
            $this->insertSessionDataToDb($session_id, $_SESSION, $uid);
            // write session data to cache
            $this->writeSessionDataToCache($session_id, $_SESSION);
            return true;
        }
        $this->updateSessionDataToDb($session_id, $_SESSION, $uid);
        // write session data to cache
        $this->writeSessionDataToCache($session_id, $_SESSION);
        return true;
    }
    /**
     * insert session data
     * @param string $session_id
     * @param array $session_data
     * @param int $uid
     * @return int: last_insert_id
     */
    private function insertSessionDataToDb($session_id, $session_data, $uid = null)
    {
        if (empty($session_id) || empty($this->model)) {
            return 0;
        }
        $session_data_map                    = [];
        $session_data_map['session_id']      = $session_id;
        $session_data_map['session_data']    = $this->encodeSessionData($session_data);
        $session_data_map['session_expires'] = time() + $this->lifetime;
        if ($uid && $uid > 0) {
            $session_data_map['uid'] = $uid;
        }

        $last_insert_id = $this->model->insert($session_data_map);
        __add_info(
            'session insert: ' . $last_insert_id,
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $last_insert_id;
    }
    /**
     * update session data to database
     * @param string $session_id
     * @param array $session_data
     * @param int $uid
     * @return int : $affected_rows
     */
    private function updateSessionDataToDb($session_id, $session_data, $uid = null)
    {
        if (empty($session_id) || empty($this->model)) {
            return 0;
        }
        $session_data_map                    = [];
        $session_data_map['session_id']      = $session_id;
        $session_data_map['session_data']    = $this->encodeSessionData($session_data);
        $session_data_map['session_expires'] = time() + $this->lifetime;
        if ($uid && $uid > 0) {
            $session_data_map['uid'] = $uid;
        }

        $where_params                       = [];
        $where_params['session_id = ']      = $session_id;
        $where_params['session_expires > '] = time();
        $affected_rows                      = $this->model->update($session_data_map, $where_params);
        __add_info(
            'session updated: ' . $affected_rows,
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $affected_rows;
    }
    /**
     * destroy session data to database
     * @param string $session_id
     * @return int: affected_rows
     */
    private function destroySessionDataToDb($session_id)
    {
        if (empty($session_id) || empty($this->model)) {
            return 0;
        }
        $session_data_map                = [];
        $session_data_map['delete_flag'] = 1;
        $where_params                    = [];
        $where_params['session_id = ']   = $session_id;
        $affected_rows                   = $this->model->update($session_data_map, $where_params);
        __add_info(
            sprintf('destroy session: %s. updated:%s', $session_id, $affected_rows),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $affected_rows;
    }
    /**
     * recycle session data to database
     * @param string $session_id
     * @return int: affected_rows
     */
    private function recycleSessionDataToDb()
    {
        if (empty($this->model)) {
            return 0;
        }
        $session_data_map                   = [];
        $session_data_map['delete_flag']    = 1;
        $where_params                       = [];
        $where_params['session_expires < '] = time();
        $where_params['delete_flag = ']     = 0;
        $affected_rows                      = $this->model->update($session_data_map, $where_params);
        __add_info(
            'recycle session updated: ' . $affected_rows,
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $affected_rows;
    }
    /**
     * update old session id with new session id
     * @param string $old_session_id
     * @param string $new_session_id
     * @return int: affected_rows
     */
    private function updateOldSessionIdWithNew($old_session_id, $new_session_id)
    {
        if (empty($this->model)) {
            return 0;
        }
        $session_data_map                = [];
        $session_data_map['session_id']  = $new_session_id;
        $session_data_map['delete_flag'] = 0;
        $where_params['session_id = ']   = $old_session_id;
        $affected_rows                   = $this->model->update($session_data_map, $where_params);
        __add_info(
            sprintf(
                'old session_id is replaced by the new one %s: [o]%s->[n]%s',
                $affected_rows ? 'succeed' : 'falied',
                $old_session_id,
                $new_session_id
            ),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $affected_rows;
    }
    /**
     * get session data from database
     * @param string $session_id
     * @return array | null
     */
    private function getSessionDataFromDb($session_id)
    {
        if (empty($session_id) || empty($this->model)) {
            return null;
        }
        $row   = $this->model->getDataBySessionId($session_id);
        $value = empty($row) ? '' : $row['session_data'];
        __add_info(
            sprintf('read session from db: %s => %s', $session_id, $value),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $value;
    }
    /**
     * get session data from database
     * @param string $session_id
     * @return int
     */
    private function countSessionDataFromDb($session_id)
    {
        if (empty($session_id) || empty($this->model)) {
            return 0;
        }

        return $this->model->queryCount(
            'session_id',
            'delete_flag = 0 AND session_id = :session_id AND session_expires >= :now',
            [':session_id' => $session_id, ':now' => time()]
        );
    }
    /**
     * get session data from cache
     * @param string $session_id
     * @return array, null if not exists
     */
    private function getSessionDataFromCache($session_id)
    {
        if (empty($session_id) || empty($this->cache)) {
            return null;
        }
        $cache_key = $this->getSessionCacheKey($session_id);
        $value     = $this->cache->get($cache_key);
        __add_info(
            sprintf('read session from cache: %s => %s', $cache_key, $value),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        return $value;
    }
    /**
     * write session data to cache
     * @param string $session_id
     * @param string $session_data
     * @param int $expire_time
     * @return void
     */
    private function writeSessionDataToCache($session_id, $session_data)
    {
        if (empty($session_id) || empty($this->cache)) {
            return;
        }
        // write to cache
        $cache_key    = $this->getSessionCacheKey($session_id);
        $session_data = $this->encodeSessionData($session_data);
        $this->cache->set($cache_key, $session_data, time() + $this->lifetime);
        __add_info(
            sprintf(
                'write session to cache: %s => %s',
                $session_id,
                var_export($session_data, true)
            ),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
    }
    /**
     * clear session data from cache
     * @param string $session_id
     * @return void
     */
    private function clearSessionDataCache($session_id)
    {
        if (empty($session_id) || empty($this->cache)) {
            return;
        }

        $cache_key = $this->getSessionCacheKey($session_id);
        $this->cache->delete($cache_key);
        __add_info(
            'delete session data from cache: ' . $session_id,
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
    }
    /**
     * encode session data
     * @param array $session_data
     * @return string: in JSON
     */
    private function encodeSessionData($session_data)
    {
        return json_encode($session_data);
    }
    /**
     * decode session data
     * @param string $session_data
     * @return array: decoded in JSON
     */
    private function decodeSessionData($session_data)
    {
        return json_decode($session_data, true);
    }
    /**
     * check is session exist
     * @param string $session_id
     * @return boolean
     */
    public function isSessionExist($session_id)
    {
        $cache_key = $this->getSessionCacheKey($session_id);
        if ($this->cache->isKeyExist($cache_key)) {
            __add_info(
                'check session exist from cache: ' . $session_id,
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return true;
        }
        return (bool) $this->countSessionDataFromDb($session_id);
    }
    /**
     * get session cache key
     * @param string $session_id
     * @return string
     */
    private function getSessionCacheKey($session_id)
    {
        return 'sess_' . $session_id;
    }
    /**
     * unserialize session data
     * @param string $data_value
     * @return array
     */
    private function unserialize($session_data)
    {
        $return_data = [];
        $offset      = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), '|')) {
                return [];
            }
            $pos     = strpos($session_data, '|', $offset);
            $num     = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data                  = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }
        return $return_data;
    }
}
