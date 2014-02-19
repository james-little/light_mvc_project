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

    const DATA_TYPE_INT = 'int';
    const DATA_TYPE_STRING = 'str';
    const DATA_TYPE_FLOAT = 'flt';
    const DATA_TYPE_ARRAY = 'array';

    /**
     * constructor
     */
    protected function __construct(){}

    /**
     * singleton
     * @return Context
     */
    public static function getInstance(){
        if(!static::$_instance){
            static::$_instance = new static();
        }
        return static::$_instance;
    }
    /**
     * Do value validation
     * @param array $data_map
     * @return array
     */
    public function validate($data_rule_map, $data_value_map) {
        if (empty($data_rule_map)) {
            return false;
        }
        $check_result_list = array();
        foreach ($data_rule_map as $variable_name => $data_rule_list) {
            @list(
                $data_rule_str,
                $data_type,
                $reg
            ) = $data_rule_list;
            $data_value = array_key_exists($variable_name, $data_value_map) ? $data_value_map[$variable_name] : null;
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

        // check data value by data rules
        if (!$this->checkDataRule($data_value, $data_rule_str)) {
            return false;
        }
        // check data value by data type
        if (!$this->checkDataType($data_value, $data_type)) {
            return false;
        }
        // check data value by regluar expression(advanced)
        if (is_string($reg)) {
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
    protected function checkDataRule($data_value, $data_rule_str) {

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
            if (preg_match('#[<>=]{2} *[^<>=]+#', $data_rule)) {
                if(is_string($data_value)) $data_value = "'{$data_value}'";
                $data_rule = str_replace(
                    array('and', 'or'),
                    array('&& ' . $data_value, '|| ' . $data_value),
                    $data_rule
                );
                eval("\$result = {$data_value} {$data_rule};");
                if(!$result) {
                    return false;
                }
            }
            // in list format like: in list [1,2,3,4]
            if (preg_match('#(not )?in list *\[([^\[\]]*)\]#', $data_rule, $tmp)) {
                $is_not = empty($tmp[1]) ? false : true;
                $allow_value_list = array_clear_empty(explode(',', $tmp[2]));
                if ($is_not && in_array_pro($data_value, $allow_value_list)) {
                    return false;
                } else if (!$is_not && !in_array_pro($data_value, $allow_value_list)) {
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
            case self::DATA_TYPE_INT:
                if (is_int($var)) {
                    return true;
                }
                break;
            case self::DATA_TYPE_FLOAT:
                if (is_float($data_value)) {
                    return true;
                }
                break;
            case self::DATA_TYPE_STRING:
                if (is_string($data_value)) {
                    return true;
                }
                break;
            case self::DATA_TYPE_ARRAY:
                if (is_array($data_value)) {
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