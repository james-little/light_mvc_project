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
 * information collector
 * =======================================================
 * for using a observer pattern to collect information
 * from the application
 *
 * @package info
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\info;

class InfoCollector
{

    const LEVEL_EMERG  = 0; // Emergency: system is unusable
    const LEVEL_ALERT  = 1; // Alert: action must be taken immediately
    const LEVEL_CRIT   = 2; // Critical: critical conditions
    const LEVEL_ERR    = 3; // Error: error conditions
    const LEVEL_WARN   = 4; // Warning: warning conditions
    const LEVEL_NOTICE = 5; // Notice: normal but significant condition
    const LEVEL_INFO   = 6; // Informational: informational messages
    const LEVEL_DEBUG  = 7; // Debug: debug messages

    const TYPE_LOGIC     = 1;
    const TYPE_EXCEPTION = 2;

    const EVENT_ON_EMERG_MESSAGE    = 'eme';
    const EVENT_ON_ERROR_MESSAGE    = 'err';
    const EVENT_ON_WARNING_MESSAGE  = 'warn';
    const EVENT_ON_CRITICAL_MESSAGE = 'critl';
    const EVENT_ON_ALERT_MESSAGE    = 'alrt';

    protected static $_instance;
    private $messages;
    private $event_handler_map;

    /**
     * get instance of exception handler
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * __construct
     */
    protected function __construct()
    {
        $this->messages = [];
    }
    /**
     * add event handler to information collector
     * @param string $type
     * @param array | string $event_handler
     * @return boolean
     */
    public function addEventHandler($type, $event_handler)
    {
        if (!in_array($type, [
            self::EVENT_ON_EMERG_MESSAGE,
            self::EVENT_ON_ERROR_MESSAGE,
            self::EVENT_ON_WARNING_MESSAGE,
            self::EVENT_ON_CRITICAL_MESSAGE,
            self::EVENT_ON_ALERT_MESSAGE,
        ])) {
            $this->add(
                'add event handler error: not supported. type: ' . $type,
                self::TYPE_LOGIC,
                self::LEVEL_DEBUG
            );
            return false;
        }
        if (is_array($event_handler)) {
            if (!is_object($event_handler[0])) {
                $this->add(
                    'add event handler error: parameter 1 must be object',
                    self::TYPE_LOGIC,
                    self::LEVEL_DEBUG
                );
                return false;
            }
            if (!is_callable([$event_handler[0], $event_handler[1]])) {
                $this->add(
                    sprintf(
                        'add event handler error: event handler not callable. handler: %s : %s ',
                        get_class($event_handler[0]),
                        $event_handler[1]
                    ),
                    self::TYPE_LOGIC,
                    self::LEVEL_DEBUG
                );
                return false;
            }
        }
        $this->event_handler_map[$type] = $event_handler;
    }
    /**
     * judge if event handler has already added
     * @param string $type
     * @return bool
     */
    public function hasEventHandler($type)
    {
        return isset($this->event_handler_map[$type]);
    }
    /**
     * remove event handler from event handler map
     * @param string $type
     */
    public function removeEventHandler($type)
    {
        if (isset($this->event_handler_map[$type])) {
            unset($this->event_handler_map[$type]);
        }
    }
    /**
     * execute event
     * @param string $type
     */
    protected function execEvent($type, $message)
    {
        if (!isset($this->event_handler_map[$type])) {
            return;
        }
        $event_handler = $this->event_handler_map[$type];
        if (is_string($event_handler)) {
            call_user_func($event_handler, $message);
        } elseif (is_array($event_handler)) {
            call_user_method($event_handler[1], $event_handler[0], $message);
        }
    }
    /**
     * get message collected
     * @param mixed $type
     * @return mixed
     */
    public function getMessages($type = null)
    {
        if ($type) {
            if (!isset($this->messages[$type])) {
                return [];
            }
            return $this->messages[$type];
        }
        return $this->messages;
    }
    /**
     * add message to collector
     * @param string $message
     * @param int $type
     * @param int $level
     */
    public function add($message, $type = self::TYPE_LOGIC, $level = self::LEVEL_INFO)
    {
        $this->messages[$type][] = ['level' => $level, 'message' => $message, 'timestamp' => time()];
        $this->execEvent($level, $message);
    }
    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->messages = null;
    }
}
