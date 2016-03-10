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

    public static $is_new_session;
    public static $old_session_id;
    public static $is_change_timely = false;
    private static $is_started = false;
    private static $session_name;
    private static $handler;



    /**
     * apply session config
     * @param array $session_config
     */
    public static function applyConfig($session_config) {
        if (empty($session_config)) return ;
        foreach ($session_config as $session_setting_key => $value) {
            if($session_setting_key == 'session_id_prefix') {
                continue ;
            }
            if ($session_setting_key == 'name' && !empty($value)) {
                self::$session_name = $value;
            } elseif ($session_setting_key == 'always_change' && !empty($value)) {
                self::$is_change_timely = true;
            } elseif ($value) {
                ini_set('session.' . $session_setting_key, $value);
            }
        }
    }
    /**
     * set session handler
     * @param $handler
     * @throws SessionException
     */
    public static function setHandler($handler) {
        if (!method_exists($handler, 'open')) {
            throw new SessionException('handler must have a method named open');
        }
        if (!method_exists($handler, 'close')) {
            throw new SessionException('handler must have a method named close');
        }
        if (!method_exists($handler, 'read')) {
            throw new SessionException('handler must have a method named read');
        }
        if (!method_exists($handler, 'write')) {
            throw new SessionException('handler must have a method named write');
        }
        if (!method_exists($handler, 'destroy')) {
            throw new SessionException('handler must have a method named destroy');
        }
        if (!method_exists($handler, 'gc')) {
            throw new SessionException('handler must have a method named gc');
        }
        if (!method_exists($handler, 'isSessionExist')) {
            throw new SessionException('handler must have a method named isSessionExist');
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
    public static function startSession($prefix = null) {

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
        $is_exist_already = $now_session_id !== null;
        // $is_exist_already = self::isSessionExist($now_session_id);
        if ($is_exist_already === false) {
            self::$is_new_session = true;
        }
        __add_info($is_exist_already ? 'session already exist' : 'new session', InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        $new_session_id = self::$is_change_timely || self::$is_new_session ? self::generateSessioId($prefix) : '';
        __add_info('new session id generated: ' . $new_session_id, InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        // set session name if needed, default to PHPSESSID
        if ($session_name) {
            session_name($session_name);
        }
        if ($is_exist_already) {
            if(self::$is_change_timely) {
                session_id($now_session_id);
                session_start();
                $tmp = $_SESSION;
                session_unset();
                session_destroy();
                self::$old_session_id = $now_session_id;
                if ($session_name) {
                    session_name($session_name);
                }
                session_id($new_session_id);
                session_start();
                $_SESSION = $tmp;
            } else {
                session_id($now_session_id);
                session_start();
            }
        } else {
            session_id($new_session_id);
            session_start();
        }
        self::$is_started = true;
        return 1;
    }
    /**
     * get session id from cookie
     * @param string | null $session_name
     * @return numeric | string
     */
    private static function getSessionName() {
        return is_null(self::$session_name) ? session_name() : self::$session_name;
    }
    /**
     * get session id from cookie
     * @param string | null $session_name
     * @return numeric | string
     */
    private static function getSessionId($session_name, $prefix = null) {

        // get session id from cookie
        $now_session_id = get_cookie_pro($session_name);
        if (empty($now_session_id)) {
            // get session id from REQUEST
            $now_session_id = empty($_REQUEST[$session_name]) ? '' : $_REQUEST[$session_name];
            __add_info(sprintf('get session id from request(%s): %s', $session_name, $now_session_id), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            ini_set('session.use_trans_sid', '1');
        } else {
            __add_info(sprintf('get session id from cookie(%s): %s', $session_name, $now_session_id), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            // not to attach session_id to url automatically
            ini_set('session.use_trans_sid', '0');
        }
        if (empty($now_session_id)) {
            // get session id from headers
            $headers = get_all_headers();
            if(isset($headers['Cookie'])) {
                if(preg_match("#{$session_name}=([^=;]+)#", $headers['Cookie'], $tmp)) {
                    $now_session_id = $tmp[1];
                }
            }
            unset($headers);
        }
        if (empty($now_session_id)) {
            // new session
            return null;
        }
        if ($prefix && substr($now_session_id, 0, strlen($prefix) + 1) != $prefix . '-') {
            __add_info('session id prefix dismatch',
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return ErrorCode::ERROR_SESSION_ID_INVALID;
        }
        return $now_session_id;
    }
    /**
     * judge if session file exists
     * @param string $session_id
     * @param string | null $prefix
     */
    private static function isSessionExist($session_id) {
        if (self::$handler !== null) {
            return self::$handler->isSessionExist($session_id);
        }
        $session_file_path = self::getSessionFile($session_id);
        if (empty($session_file_path)) {
            return false;
        }
        __add_info('session file: ' . $session_file_path,
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        return is_file($session_file_path);
    }

    /**
     * get session file name
     * @param string $session_id
     * @return session file name
     */
    private static function getSessionFile($session_id) {
        if (empty($session_id)) {
            return false;
        }
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
    private static function generateSessioId($prefix = null) {

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
    public static function getIsStarted() {
        return self::$is_started;
    }
    /**
     * close session
     */
    public static function closeSession() {
        session_write_close();
        self::$handler = null;
        self::$is_started = false;
    }
    /**
     * destroy session
     */
    public static function destroySession() {
        session_destroy();
    }
    /**
     * unlink session file
     */
    public static function unlinkSessionFile($prefix = null) {
        $session_id = self::getSessionId(self::getSessionName(), $prefix);
        $session_file_path = self::getSessionFile($session_id);
        if (empty($session_file_path)) return ;
        @unlink($session_file_path);
    }
}