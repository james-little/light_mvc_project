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
 * AppException
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\exception;

use lightmvc\ErrorMessage;
use lightmvc\exception\ExceptionErrorConverter;

class ExceptionFactory
{
    private static $err_message;
    private static $exception_err_converter;

    private static function init()
    {
        if (!self::$err_message) {
            self::$err_message= ErrorMessage::getInstance();
        }
        if (!self::$err_message) {
            self::$exception_err_converter= ExceptionErrorConverter::getInstance();
        }
    }

    public static function create($exception_class, $exception_code, $params = null)
    {
        $error_code = self::$exception_err_converter::get($exception_code);
        $message = self::$err_message::getErrorMessage($error_code);
        $message = vsprintf($message, $params);
        return new $exception_class($message, $exception_code);
    }
}
