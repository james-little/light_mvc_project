<?php
namespace lightmvc\auth;

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
 */
use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;

abstract class AbstractAuthLogic implements AuthLogicInterface
{

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
    public function doAuthFailed()
    {
        throw new AppException('auth failed', ExceptionCode::APP_AUTH_FAILED);
    }
}
