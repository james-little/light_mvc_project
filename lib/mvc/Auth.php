<?php

/**
 * Auth
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class Auth {

    protected $_url;
    protected $_callback_array;

    /**
     * __construct
     * @param string $url
     */
    public function __construct($url, array $callback_array) {
        $this->_url = $url;
        $this->_callback_array = $callback_array;
    }

    /**
     * auth
     */
    public function auth() {

        $auth_method = $this->_callback_array['auth'];
        $auth = call_user_func_array($auth_method, array($this->_url));
        if ($auth) {
            $this->callSuccess();
        } else {
            $this->callFailed();
        }
    }
    /**
     * method invoke when authentication success
     */
    protected function callSuccess() {
        $success_method = empty($this->_callback_array['success']) ? '' : $this->_callback_array['success'];
        if (empty($success_method)) {
            return ;
        }
        call_user_func($success_method);
    }
    /**
     * method invoke when authentication failed
     */
    protected function callFailed() {
        $failed_method = empty($this->_callback_array['failed']) ? '' : $this->_callback_array['failed'];
        if (empty($failed_method)) {
            return ;
        }
        call_user_func($failed_method);
    }
}