<?php
namespace auth;

interface AuthLogicInterface {

    /**
     * authentication
     * @return bool
     */
    public function doAuth($params = null);
    /**
     * logic when authentication success
     */
    public function doAuthSuccess();
    /**
     * logic when authentication failed
     */
    public function doAuthFailed();
}
