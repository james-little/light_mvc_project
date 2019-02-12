<?php
/**
 *  Copyright 2016 Koketsu
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
 * ==============================================================================
 */
namespace lightmvc;

class ErrorMessage
{

    private $messages;
    private static $instance;
    /**
     * __construct
     */
    private function __construct()
    {
        $this->messages = include __DIR__ . '/error/error_message_mapping.php';
    }
    /**
     * get instance
     * @return ErrorMessage
     */
    public static function getInstance()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }
    /**
     * add message mapping
     * @param array $message_mapping
     *        error_code => error_message
     * @return bool
     */
    public function addMessageMapping($message_mapping)
    {
        if (!is_array($message_mapping)) {
            return false;
        }
        foreach ($message_mapping as $key => $val) {
            $this->messages[$key] = $val;
        }
        return true;
    }
    /**
     * get error message
     * @param  int $error_code
     * @return string
     */
    public function getErrorMessage($error_code)
    {
        return isset($this->messages[$error_code]) ? $this->messages[$error_code] : '';
    }
}
