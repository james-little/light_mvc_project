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
 * Auth
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

use lightmvc\auth\AuthLogicInterface;

class Auth
{
    protected $_auth_logic;
    protected $_params;

    /**
     * __construct
     * @param string $url
     */
    public function __construct(AuthLogicInterface $auth_logic, array $params = null)
    {
        $this->_auth_logic = $auth_logic;
        $this->_params     = $params;
    }
    /**
     * auth
     */
    public function auth()
    {
        $is_auth_passed = $this->_auth_logic->doAuth($this->_params);
        if ($is_auth_passed) {
            $this->_auth_logic->doAuthSuccess();
        } else {
            $this->_auth_logic->doAuthFailed();
        }
    }
}
