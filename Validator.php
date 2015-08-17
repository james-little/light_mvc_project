<?php

/**
 * Validator
 * =======================================================
 * Validator for doing data validation
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

class Validator {

    protected static $_instance;

    /**
     * constructor
     */
    protected function __construct(){}

    /**
     * singleton
     * @return Context
     */
    public static function getInstance() {
        if(static::$_instance !== null) {
            return static::$_instance;
        }
        static::$_instance = new static();
        return static::$_instance;
    }
    /**
     * Do value validation
     * @param array $data_rule_map
     *  - must be like: array(
     *       %variable_name1% => array('value' => %variable_value1%, 'rule' => %rule1%, 'type' => %type1%, 'reg' => %reg1%),
     *       %variable_name2% => array('value' => %variable_value2%, 'rule' => %rule2%, 'type' => %type2%, 'reg' => %reg2%),
     *       ...
     *   )
     *   rule:
     *       1. split by ';' when multi-condition
     *       2. sample:
     *              $var != null   : not null
     *               !empty($var)  : not empty
     *              $var >(=) 2    : >(=) 2
     *              $var <(=) 100  : <(=) 100
     *              $var > 50
     *              && $var < 100  : > 50 and < 100
     *              $var < 50
     *              || $var > 100  : < 50 or > 100
     *              in_array(1,2)  : in list[1,2]
     *              !in_array(1,2) : not in list [1,2]
     *       3. reg: put regular express you want to use to validate
     * @return array (
     *     %variable_name1% => %validate_variable_result1%,
     *     %variable_name2% => %validate_variable_result2%
     *     ...
     * )
     */
    public function validate($data_rule_map) {
        if (empty($data_rule_map)) {
            return false;
        }
        $check_result_list = array();
        foreach ($data_rule_map as $variable_name => $data_rule_list) {
            $data_value = array_key_exists('value', $data_rule_list) ? $data_rule_list['value'] : null;
            $data_rule_str = array_key_exists('rule', $data_rule_list) ? $data_rule_list['rule'] : '';
            $data_type = array_key_exists('type', $data_rule_list) ? $data_rule_list['type'] : '';
            $reg = array_key_exists('reg', $data_rule_list) ? $data_rule_list['reg'] : '';
            $check_result_list[$variable_name] = $this->checkValue($data_value, $data_rule_str, $data_type, $reg);
        }
        return $check_result_list;
    }

    /**
     * check data value
     * @param mixed $data_value
     * @param string $data_rule_str
     * @param string $data_type
     * @param string | null $reg
     * @return bool
     */
    protected function checkValue($data_value, $data_rule_str, $data_type, $reg = null) {

        // check data value by data type
        if (!$this->checkDataType($data_value, $data_type)) {
            return false;
        }
        // check data value by data rules
        if (!$this->checkDataRule($data_type, $data_value, $data_rule_str)) {
            return false;
        }
        // check data value by regluar expression(advanced)
        if (is_string($reg) && strlen($reg) > 0) {
            if (!$this->checkDataReg($data_value, $reg)) {
                return false;
            }
        }
        return true;
    }

    /**
     * check data value by rule list
     * $data_rule_str is string like:
     *         not null; > 1 ; not empty
     * defines serval data rules separated by ';'
     * @param mixed $data_value
     * @param string $data_rule_str
     * @return boolean
     */
    protected function checkDataRule($data_type, $data_value, $data_rule_str) {

        $data_rule_str = str_replace('  ', ' ', trim($data_rule_str));
        $data_rule_list = explode(';', $data_rule_str);
        unset($data_rule_str);
        if (empty($data_rule_list)) {
            // data rule list empty means not need to check
            return true;
        }
        // data rule
        foreach ($data_rule_list as $data_rule) {
            $data_rule = strtolower(trim($data_rule));
            if ($data_rule == 'not null' && $data_value === null) {
                return false;
            }
            if ($data_rule == 'not empty' && empty($data_value)) {
                return false;
            }
            // operator
            $tmp = array();
            if (preg_match('#(len|count)? *[!<>=]{1,2} *[^<>=]+#', $data_rule, $tmp)) {
                $command = null;
                switch ($data_type) {
                    case Datatype::DATA_TYPE_STRING:
                        if (!empty($tmp[1]) && $tmp[1] == 'len') {
                            $data_value_str = 'strlen($data_value)';
                        } else {
                            $data_value_str = "'{$data_value}'";
                        }
                        $data_rule = str_replace(
                            array('len', 'and', 'or'),
                            array('', '&& ' . $data_value_str, '|| ' . $data_value_str),
                            $data_rule
                        );
                        $command = "\$result = {$data_value_str} {$data_rule};";
                        break;
                    case Datatype::DATA_TYPE_INT:
                        $data_rule = str_replace(
                            array('and', 'or'),
                            array('&& ' . $data_value, '|| ' . $data_value),
                            $data_rule
                        );
                        $command = "\$result = {$data_value} {$data_rule};";
                        break;
                    case Datatype::DATA_TYPE_ARRAY:
                        if (empty($tmp[1]) || $tmp[1] != 'count') {
                            return false;
                        }
                        $data_value_str = 'count($data_value)';
                        $data_rule = str_replace(
                            array('count', 'and', 'or'),
                            array('', '&& ' . $data_value_str, '|| ' . $data_value_str),
                            $data_rule
                        );
                        $command = "\$result = {$data_value_str} {$data_rule};";
                        break;
                    default:
                        break;
                }
                if(!$command) {
                    return false;
                }
                eval($command);
                if(!$result) {
                    return false;
                }
            }
            // in list format like: in list [1,2,3,4]
            if (preg_match('#(not )?in list *\[([^\[\]]*)\]#', $data_rule, $tmp)) {
                $is_not = empty($tmp[1]) ? false : true;
                $allow_value_list = array_clear_empty(explode(',', str_replace(' ', '', $tmp[2])));
                $is_in_array = in_array_pro($data_value, $allow_value_list);
                if ($is_not && $is_in_array) {
                    // not in list
                    return false;
                } else if (!$is_not && !$is_in_array) {
                    // in list
                    return false;
                }
                unset($allow_value_list);
            }
        }
        return true;
    }
    /**
     * check data type
     * @param mixed $data_value
     * @param string $data_type
     * @return boolean
     */
    protected function checkDataType($data_value, $data_type) {
        // check data type
        switch ($data_type) {
            case Datatype::DATA_TYPE_INT:
                if (is_int($data_value)) {
                    return true;
                }
                break;
            case Datatype::DATA_TYPE_FLOAT:
                if (is_float($data_value)) {
                    return true;
                }
                break;
            case Datatype::DATA_TYPE_STRING:
                if (is_string($data_value)) {
                    return true;
                }
                break;
            case Datatype::DATA_TYPE_ARRAY:
                if (is_array($data_value)) {
                    return true;
                }
                break;
            case Datatype::DATA_TYPE_BOOL:
                if (is_bool($data_value)) {
                    return true;
                }
                break;
        }
        return false;
    }
    /**
     * check data by regular expression
     * @param mixed $data_value
     * @param string $reg
     * @return bool
     */
    protected function checkDataReg($data_value, $reg) {
        return preg_match($reg, $data_value);
    }
}