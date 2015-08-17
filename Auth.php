<?php

/**
 * Auth
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
use auth\AuthLogicInterface;

class Auth {

    protected $_auth_logic;
    protected $_params;

    /**
     * __construct
     * @param string $url
     */
    public function __construct(AuthLogicInterface $auth_logic, array $params = null) {
        $this->_auth_logic = $auth_logic;
        $this->_params = $params;
    }
    /**
     * auth
     */
    public function auth() {
        $is_auth_passed = $this->_auth_logic->doAuth($this->_params);
        if ($is_auth_passed) {
            $this->_auth_logic->doAuthSuccess();
        } else {
            $this->_auth_logic->doAuthFailed();
        }
    }
}