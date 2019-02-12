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
 * Queue Processor Parser
 * =======================================================
 * parse server response data from server
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @version 1.0
 **/
namespace lightmvc\queue\processor;

use lightmvc\queue\processor\rule\QueueProcessorParserRule;
use lightmvc\ClassLoader;

class QueueProcessorParser
{

    protected $rule_list;

    /**
     * add rule to rule list
     * @param string $class_name
     * @return bool
     */
    public function addRule($class_name)
    {
        if (class_exists($class_name)) {
            $parser_rule = ClassLoader::loadClass($class_name);
            if (!$parser_rule instanceof QueueProcessorParserRule) {
                return false;
            }
        }
        if (is_array($this->rule_list) && array_key_exists($class_name, $this->rule_list)) {
            return true;
        }
        $this->rule_list[$class_name] = $parser_rule;
        return true;
    }

    /**
     * remove rule from rule list
     * @param string $class_name
     * @return bool
     */
    public function removeRule($class_name)
    {
        if (isset($this->rule_list[$class_name])) {
            unset($this->rule_list[$class_name]);
            return true;
        }
        return false;
    }

    /**
     * check if the rule is already exist
     * @param string $class_name
     * @return bool
     */
    public function hasRule($class_name)
    {
        return isset($this->rule_list[$class_name]);
    }

    /**
     * Parse response into array with succeeded and error_code
     * Add rule to parser. The earlier rule be put into the list would be
     * processed first.
     * @param array | string $response
     * @return boolean | array
     *     . succeeded
     *     . error_code
     */
    public function parse($response)
    {
        if (empty($this->rule_list)) {
            return false;
        }
        foreach ($this->rule_list as $rule) {
            $parsed_result = $rule->parse($response);
            if (is_array($parsed_result)) {
                return $parsed_result;
            }
        }
        return false;
    }

    /**
     * __destruct
     */
    public function __destruct()
    {
        $this->rule_list = null;
    }
}
