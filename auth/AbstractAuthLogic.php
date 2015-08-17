<?php
namespace auth;

use exception\ExceptionCode;
use exception\AppException;

abstract class AbstractAuthLogic implements AuthLogicInterface {

    /**
     * authentication
     * @param mixed $param
     * @return bool
     */
    abstract public function doAuth($params = null);
    /**
     * logic when authentication success
     */
    abstract public function doAuthSuccess();
    /**
     * do auth failed
     * @throws AppException
     */
    public function doAuthFailed() {
        throw new AppException('auth failed', ExceptionCode::APP_AUTH_FAILED);
    }
}
