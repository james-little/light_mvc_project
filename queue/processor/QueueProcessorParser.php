<?php
namespace queue\processor;

use exception\QueueException,
    queue\processor\rule\QueueProcessorParserRule;


/**
 * Queue Processor Parser
 * =======================================================
 * parse server response data from server
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @version 1.1
 **/
class QueueProcessorParser  {

    protected $rule_list;

    /**
     * add rule to rule list
     * @param string $class_name
     * @return bool
     */
    public function addRule($class_name) {
        if (class_exists($class_name)) {
            $parser_rule = \ClassLoader::loadClass($class_name);
            if (!$parser_rule instanceof QueueProcessorParserRule) {
                return false;
            }
        }
        if(is_array($this->rule_list) && array_key_exists($class_name, $this->rule_list)) {
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
    public function removeRule($class_name) {
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
    public function hasRule($class_name) {
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
    public function parse($response) {

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
    public function __destruct() {
        $this->rule_list = null;
    }


}