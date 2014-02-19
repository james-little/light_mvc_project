<?php

use exception\SessionException;

/**
 * Session (secured)
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
use info\InfoCollector;

class Session {

    private static $is_started = false;
    private static $session_name;
    public static $old_session_id;
    private static $handler;
    public static $is_new_session;

    /**
     * apply session config
     * @param array $session_config
     */
    static public function applyConfig($session_config) {
        if (empty($session_config)) return ;
        foreach ($session_config as $session_setting_key => $value) {
            if($session_setting_key == 'session_id_prefix') {
                continue ;
            }
            if ($session_setting_key == 'name' && !empty($session_config['name'])) {
                self::$session_name = $session_config['name'];
            } elseif($value) {
                ini_set('session.' . $session_setting_key, $value);
            }
        }
    }
    /**
     * set session handler
     * @param $handler
     * @throws SessionException
     */
    static public function setHandler($handler) {
    	if (!method_exists($handler, 'open')) {
    		throw new SessionException(__message('handler must have a method named open'));
    	}
    	if (!method_exists($handler, 'close')) {
    		throw new SessionException(__message('handler must have a method named close'));
    	}
    	if (!method_exists($handler, 'read')) {
    		throw new SessionException(__message('handler must have a method named read'));
    	}
    	if (!method_exists($handler, 'write')) {
    		throw new SessionException(__message('handler must have a method named write'));
    	}
    	if (!method_exists($handler, 'destroy')) {
    		throw new SessionException(__message('handler must have a method named destroy'));
    	}
    	if (!method_exists($handler, 'gc')) {
    		throw new SessionException(__message('handler must have a method named gc'));
    	}
    	if (!method_exists($handler, 'isSessionExist')) {
    		throw new SessionException(__message('handler must have a method named isSessionExist'));
    	}
    	self::$handler = $handler;
    	session_set_save_handler(
	    	array(self::$handler, 'open'),
	    	array(self::$handler, 'close'),
	    	array(self::$handler, 'read'),
	    	array(self::$handler, 'write'),
	    	array(self::$handler, 'destroy'),
	    	array(self::$handler, 'gc')
    	);
    }
    /**
     * start session
     * @param string | null $prefix
     * @return bool | numeric
     */
    static public function startSession($prefix = null) {

        if (self::$is_started) {
            return ErrorCode::ERROR_SESSION_ALREADY_START;
        }
        if ($prefix && preg_match('#[^\da-zA-Z]#', $prefix)) {
            return ErrorCode::ERROR_SESSION_ID_INVALID;
        }
        $session_name = self::getSessionName();
        $now_session_id = self::getSessionId($session_name, $prefix);
        if ($now_session_id < 0) {
            // return error code
            return $now_session_id;
        }
        $is_exist_already = self::isSessionExist($now_session_id);
        if ($is_exist_already === false) self::$is_new_session = true;
        __add_info(__message($is_exist_already ? 'session already exist' : 'new session'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        $new_session_id = self::generateSessioId($prefix);
        __add_info(__message('new session id generated: %s', array($new_session_id)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        // set session name if needed, default to PHPSESSID
        if ($session_name) {
            session_name($session_name);
        }
        if ($is_exist_already) {
            session_id($now_session_id);
            session_start();
            __add_info(__message('old session started: %s, using %s', array($now_session_id, session_id())), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            $tmp = $_SESSION;
            // for db implementation
            self::$old_session_id = $now_session_id;
            session_destroy();
            __add_info(__message('old session destroyed: %s', array($now_session_id)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            if ($session_name) {
                session_name($session_name);
            }
            session_id($new_session_id);
            session_start();
            __add_info(__message('new session started: %s, using %s', array($new_session_id, session_id())), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            $_SESSION = $tmp;
        } else {
            session_id($new_session_id);
            session_start();
            __add_info(__message('new session started: %s, using %s', array($new_session_id, session_id())), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
        self::$is_started = true;
        return 1;
    }
    /**
     * get session id from cookie
     * @param string | null $session_name
     * @return numeric | string
     */
    static private function getSessionName() {
        return is_null(self::$session_name) ? session_name() : self::$session_name;
    }
    /**
     * get session id from cookie
     * @param string | null $session_name
     * @return numeric | string
     */
    static private function getSessionId($session_name, $prefix = null) {

        // get session id from cookie
        $now_session_id = get_cookie_pro($session_name);
        if (empty($now_session_id)) {
            // get session id from REQUEST
            $now_session_id = empty($_REQUEST[$session_name]) ? '' : $_REQUEST[$session_name];
            __add_info(__message('get session id from request(%s): %s', array($session_name, $now_session_id)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            ini_set('session.use_trans_sid', '1');
        } else {
            __add_info(__message('get session id from cookie(%s): %s', array($session_name, $now_session_id)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            // not to attach session_id to url automatically
            ini_set('session.use_trans_sid', '0');
        }
        if (empty($now_session_id)) {
            // new session
            return null;
        }
        if ($prefix && !preg_match("#^{$prefix}\-#", $now_session_id)) {
            __add_info(__message('session id prefix dismatch'), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return ErrorCode::ERROR_SESSION_ID_INVALID;
        }
        return $now_session_id;
    }
    /**
     * judge if session file exists
     * @param string $session_id
     * @param string | null $prefix
     */
    static private function isSessionExist($session_id) {
    	if (self::$handler) {
    		return self::$handler->isSessionExist($session_id);
    	}
        $session_file_path = self::getSessionFile($session_id);
        if (empty($session_file_path)) return false;
        __add_info(__message('session file: %s', array($session_file_path)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        return file_exists($session_file_path);
    }

    /**
     * get session file name
     * @param string $session_id
     * @return session file name
     */
    static private function getSessionFile($session_id) {
        if (empty($session_id)) return false;
        $session_file_path = trim(session_save_path());
        preg_match('#^((\d);)?([^\d]+)#', $session_file_path, $tmp);
        $file_depth = isset($tmp[2]) ? $tmp[2] : 0;
        $session_file_path = $tmp[3];
        if ($file_depth) {
            for ($i = 0; $i < $file_depth; $i ++) {
                $session_file_path .= DIRECTORY_SEPARATOR . substr($session_id, $i, 1);
            }
        }
        $session_file_path .= DIRECTORY_SEPARATOR . 'sess_' . $session_id;
        return $session_file_path;
    }
    /**
     * generate new session id
     * @param string | null $prefix
     * @return string
     */
    static private function generateSessioId($prefix = null) {

        $session_id = '';
        if (is_string($prefix) && strlen($prefix) > 0) {
            $session_id = $prefix . '-';
        }
        $session_id .= md5(uniqid(mt_rand(), 1));
        return $session_id;
    }
    /**
     * get is session started
     */
    static public function getIsStarted() {
        return self::$is_started;
    }
    /**
     * close session
     */
    static public function closeSession() {
    	__add_info(__message('session closed: %s', array(session_id())), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
    	self::$old_session_id = null;
        session_write_close();
    }
    /**
     * destroy session
     */
    static public function destroySession() {
    	__add_info(__message('session destroyed: %s', array(session_id())), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
    	self::$old_session_id = null;
        session_destroy();
    }
    /**
     * unlink session file
     */
    static public function unlinkSessionFile($prefix = null) {
        $session_id = self::getSessionId(self::getSessionName(), $prefix);
        $session_file_path = self::getSessionFile($session_id);
        if (empty($session_file_path)) return ;
        @unlink($session_file_path);
    }
}