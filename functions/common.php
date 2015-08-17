<?php
use info\InfoCollector;

// -----------------------  SYSTEM MESSAGES -----------------------------------//
/**
 * i18n translate string
 * @param string $message
 * @param array $param
 * @return string
 */
function __message($message, $param = null, $textdomain = null) {

    if (defined('APPLICATION_LANG') && extension_loaded('gettext')) {
        if($textdomain) {
            $message = dgettext($textdomain, $message);
        } else {
            $message = gettext($message);
        }
    }
    if (empty($param)) {
        return $message;
    }
    $command = '$message = vsprintf($message, $param);';
    @eval($command);
    return $message;
}

/**
 * add to information collector
 * @param string $message
 */
function __add_info(
    $message, $type = InfoCollector::TYPE_LOGIC, $level = InfoCollector::LEVEL_INFO) {
    if (defined('ENABLE_INFO_COLLECT') && ENABLE_INFO_COLLECT === false) {
        return ;
    }
    global $GLOBALS;
    if (empty($GLOBALS['information_collector'])) {
        $GLOBALS['information_collector'] = InfoCollector::getInstance();
    }
    $GLOBALS['information_collector']->add($message, $type, $level);
}
/**
 * get exception message
 * @param Exception $e
 * @return string
 */
function __exception_message(Exception $e) {
    return $e->getFile() . ':' . $e->getLine() . ' :: '
    . $e->getMessage();
}

// -----------------------  ARRAY -----------------------------------//

/**
 * clear empty, null or duplicated value in array
 * @param array $array
 * @return array
 */
function array_clear_empty($array) {
    if(!is_array($array)) {
        return $array;
    }
    foreach($array as $key => $val) {
        if($val === null || $val == '') {
            unset($array[$key]);
        }
    }
    return $array;
}
/**
 * improved in_array function
 * @param mixed $value
 * @param array $array
 * @return boolean
 */
function in_array_pro($value, $array) {
    if (empty($array)) {
        return false;
    }
    return
        count($array) > 50 ?
        array_key_exists($value, @array_flip($array)) :
        in_array($value, $array);
}
/**
 * filter array with specified keys
 * example:
 *     $data_map = array(
 *         'a' => 1,
 *         'b' => 2,
 *         'c' => 3
 *     )
 *     $column_array = array('a', 'c');
 *     filter_array($data_map, $column_array);
 *
 *     output:
 *         array('a' => 1, 'c' => 3)
 * @param array $data_map
 * @param array $column_array
 */
function filter_array($data_map, $column_array, $not_empty = false) {
    if(empty($data_map) || !is_array($column_array)) return array();
    $array = array_intersect_key($data_map, array_flip($column_array));
    if ($not_empty === false) return $array;
    return array_clear_empty($array);
}
/**
 * get specified data from data
 * example:
 *     $data_list = array(
 *         array('a' => 11, 'b' => 12, 'c' => 13),
 *         array('a' => 21, 'b' => 22, 'c' => 23),
 *         array('a' => 31, 'b' => 32, 'c' => 33)
 *     )
 *     $column_array = array('a', 'c');
 *     array_get_column($data_map, $column_array);
 *
 *     output:
 *     array(
 *         array('a' => 11, 'c' => 13),
 *         array('a' => 21, 'c' => 23),
 *         array('a' => 31, 'c' => 33)
 *     )
 * @param array $array_list
 * @param array $column_list
 */
function array_get_column($array_list, $column_list) {
    if (empty($column_list)) return array();
    $data_list = array();
    foreach ($column_list as $column) {
        foreach ($array_list as $key => $array) {
            if (array_key_exists($column, $array)) {
                $data_list[$key][$column] = $array[$column];
            }
        }
    }
    return $data_list;
}
/**
 * get specified data from array
 * @example
 *     given array:
 *         $records = array(
 *           array('id' => 2135, 'first_name' => 'John'),
 *           array('id' => 2131, 'first_name' => 'Sally'),
 *           array('id' => 2137, 'first_name' => 'Peter'),
 *          );
 *          $data_list = array_get_column_value($records, 'id');
 *
 *      result would be:
 *          array(2135,2131,2137)
 * @param array $array_list
 * @param string $column
 * @param boolean $enable_duplicate
 * @return array
 */
function array_get_column_value($array_list, $column, $enable_duplicate = false) {
    if(empty($array_list)) {
        return array();
    }
    if (empty($column) || !is_string($column)) {
        return array();
    }
    $data_list = array();
    foreach ($array_list as $key => $array) {
        if (array_key_exists($column, $array)) {
            $data_list[] = $array[$column];
        }
    }
    if(!$enable_duplicate) {
        $data_list = array_unique($data_list);
    }
    return $data_list;
}

/**
 * make multi-value string.
 *     example: 2,3,1,5 => 1,2,3,5
 * @param array|string $data
 * @return string
 */
function make_multi_val_string($data) {
    if (empty($data)) return '';
    if (is_string($data) || is_numeric($data)) {
        // is $data is string then make it into array
        $data = explode(',', $data);
    }
    $data = array_clear_empty($data);
    if (empty($data)) return '';
    $data = array_unique($data);
    sort($data, SORT_NUMERIC);
    return implode(',', $data);
}

// -----------------------  STRING -----------------------------------//
/**
 * convert var to string
 * @param mixed $val
 * @return string
 */
function convert_string($val) {
    if(is_string($val)) {
        return $val;
    }
    if (is_object($val) || is_array($val)) {
        $val = _serialize($val);
    }
    if (is_bool($val)) {
        $val = intval($val);
    }
    return "{$val}";
}
/**
 * serialize
 * @param $val
 * @return string
 */
function _serialize($val) {
    if (function_exists('igbinary_serialize')) {
        $val = igbinary_serialize($val);
    } else {
        $val = serialize($val);
    }
    return $val;
}

/**
 * unserialize
 * @param $val
 * @return mixed
 */
function _unserialize($val) {
    if (function_exists('igbinary_unserialize')) {
        $val = igbinary_unserialize($val);
    } else {
        $val = unserialize($val);
    }
    return $val;
}

//-----------------------  ENCODING -----------------------------------//
/**
 * convert string into utf-8 encoding
 * @param string $string
 * @return string
 */
function convert2utf8($string, $from_encode = null) {
    if (empty($string)) return '';
    if (!$from_encode) {
        $from_encode = mb_detect_encoding($string, "auto", true);
    }
    if (!$from_encode) {
        // return original string when auto detect encoding failed
        return $string;
    }
    $from_encode = strtolower($from_encode);
    if ($from_encode == 'utf-8' || $from_encode == 'utf8') {
        return $string;
    }
    if (extension_loaded('iconv')) {
        return iconv($from_encode, 'UTF-8//IGNORE', $string);
    }
    return mb_convert_encoding($string, 'UTF-8', $from_encode);

}

/**
 * parse csv file to array
 * @param string $filename
 * @param string $delimiter
 * @return array
 */
function csv_to_array($file_name, $delimiter = ',') {

    $handle = @fopen($file_name, 'r');
    if ($handle === false) {
        return array();
    }
    $data = array();
    while (($row = @fgetcsv($handle, 1000, $delimiter)) !== false) {
        $data[] = $row;
    }
    @fclose($handle);
    unset($row);
    return $data;
}

//-----------------------  FILE -----------------------------------//
/**
 * make file by daily/monthly/weekly/hourly mode
 * @param string $base_dir
 * @param string $type
 * @param bool $is_make_file
 * @return mixed
 *          false: failed
 *          string: file_name maded
 */
function make_file($base_dir, $type = 'monthly', $is_make_file = true) {

    if (!in_array($type, array('daily', 'monthly', 'hourly', 'weekly'))) {
        return false;
    }
    $file_name = '';
    $sub_folder = '';
    switch ($type) {
        case 'monthly':
            $sub_folder = $base_dir;
            $file_name = date('Ym') . '.log';
            break;
        case 'weekly':
            $sub_folder = "{$base_dir}/" . date('Ym');
            $file_name = date('YW') . '.log';
            break;
        case 'daily':
            $sub_folder = "{$base_dir}/" . date('Ym');
            $file_name = date('Ymd') . '.log';
            break;
        case 'hourly':
            $sub_folder = "{$base_dir}/" . date('Ym');
            $file_name = date('YmdH') . '.log';
            break;
    }
    if (!is_dir($sub_folder)) {
        @mkdir($sub_folder, 0777, true);
        @chmod($sub_folder, 0777);
    }
    $full_path = $sub_folder . '/' . $file_name;
    if(!$is_make_file) {
        return $full_path;
    }
    @file_put_contents($full_path, '', FILE_APPEND);
    if (!is_file($full_path)) {
        return $file_name;
    }
    return $full_path;
}
/**
 * check is directory usable
 * @param string $dir
 * @param string $chmod
 * @return boolean
 */
function directory_make_usable($dir, $chmod = 0777) {
    // If it doesn't exist, and can't be made
    if(!is_dir($dir) && !mkdir($dir, $chmod, true)) return false;
    // If it isn't writable, and can't be made writable
    if(!is_writable($dir) && !chmod($dir, $chmod)) return false;
    return true;
}

/**
 * delete folder and files below
 * @param  string  $dir
 * @param  boolean $is_delete_self
 * @return void
 */
function del_dir_files($dir) {
    if(!is_dir($dir)) {
        return ;
    }
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if($file == '.' || $file == '..') {
            continue ;
        }
        $fullpath = $dir . '/' . $file;
        if(is_dir($fullpath)) {
            continue ;
        }
        unlink($fullpath);
    }
    closedir($dh);
}

/**
 * delete dir and files below recursively
 * @param  string  $dir
 * @param  boolean $is_delete_self
 * @return void
 */
function del_dir_recur($dir, $is_delete_self = false) {
    if(!is_dir($dir)) {
        return ;
    }
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if($file == '.' || $file == '..') {
            continue ;
        }
        $fullpath = $dir . '/' . $file;
        if (is_dir($fullpath)) {
            del_dir_recur($fullpath, true);
        } else {
            unlink($fullpath);
        }
    }
    closedir($dh);
    if($is_delete_self) {
        rmdir($dir);
    }
}

//-----------------------  CRYPT -----------------------------------//

/**
 * decode encoded data
 * @param string $crypt_value
 * @param string $crypt_key
 * @param boolean $base64_encode
 * @return mixed null if decode failed
 */
function decode_crypt_value($crypt_value, $crypt_key, $base64_encode = false) {
    if (!function_exists('mcrypt_module_open')) return null;
    if (!function_exists('mcrypt_generic_init')) return null;
    if($base64_encode) {
        $crypt_value = base64_decode($crypt_value);
    } else {
        if (phpversion() < '5.4.0') {
            $crypt_value = hex2bin5_3($crypt_value);
        } else {
            if(strlen($crypt_value) % 2 > 0) {
                return null;
            }
            $crypt_value = hex2bin($crypt_value);
        }
    }
    if(!$crypt_value) {
        return null;
    }
    if(!$crypt_key) {
        return null;
    }
    $iv = strrev($crypt_key);
    $td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
    mcrypt_generic_init($td, $crypt_key, $iv);
    $crypt_value = mdecrypt_generic($td, $crypt_value);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    $crypt_value = utf8_encode(trim($crypt_value));
    $crypt_value = preg_replace('#[^\w\s\t\n"\'-=~@`\?_\*\^\|\#$%&\(\);:/\\!,\.<>\+{}\[\]]#i', '', $crypt_value);
    return $crypt_value;
}

/**
 * make encode string
 * @param string $string
 * @param string $crypt_key
 * @param boolean $base64_encode
 * @return string encoded data
 */
function get_crypt_value($string, $crypt_key, $base64_encode = false) {
    if (!function_exists('mcrypt_module_open')) return null;
    if (!function_exists('mcrypt_generic_init')) return null;
    if(!$crypt_key) {
        return null;
    }
    $blocksize = 16;
    $pad = $blocksize - (strlen($string) % $blocksize);
    $string .= str_repeat(chr($pad), $pad);
    $iv = strrev($crypt_key);
    $td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
    mcrypt_generic_init($td, $crypt_key, $iv);
    $string = mcrypt_generic($td, $string);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    if($base64_encode) {
        return base64_encode($string);
    }
    return bin2hex($string);
}
/**
 * hex -> binary
 * @param string $hexdata
 */
function hex2bin5_3($hexdata) {
    $bindata = '';
    $len = strlen($hexdata);
    for ($i = 0; $i < $len; $i += 2) {
        $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
    }
    return $bindata;
}


//-----------------------  RANDOM -----------------------------------//
/**
 * Create a random string
 * @param int $length
 * @param bool $with_symbol
 * @return string
 */
function get_random_string($length, $with_symbol = false) {
    $str = '';
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($with_symbol) {
        $chars .= '#$%&();:/\!,.<>+{}[]';
    }
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}
/**
 * Create a random 32 character MD5 token
 * @return string
 */
function token() {
    return md5(str_shuffle(chr(mt_rand(32, 126)) . uniqid() . microtime(true)));
}

//-----------------------  DATE -----------------------------------//
/**
 * format to UTC
 * @param number | string $date
 */
function format_2_UTC($date) {
    // Get the default timezone
    $default_tz = date_default_timezone_get();
    // Set timezone to UTC
    date_default_timezone_set('UTC');
    // convert datetime into UTC
    if (is_string($date)) {
        $date = strtotime($date);
    }
    $utc_format = date('Y/m/d\TG:i:s\Z', $date);
    // Might not need to set back to the default but did just in case
    date_default_timezone_set($default_tz);
    return $utc_format;
}

//-----------------------  ECHO BIG STRING -----------------------------------//
/**
 * echo big string
 * @param string $string
 * @param int $buffer_size
 */
function echo_pro($string, $buffer_size = 8192) {
    $len = strlen($string);
    if($len <= $buffer_size) {
        echo $string;
        return ;
    }
    for ($chars = $len - 1, $start = 0; $start <= $chars; $start += $buffer_size) {
        echo substr($string, $start, $buffer_size);
    }
}

//----------------------- CONSOLE -----------------------------------//
/**
 * read user input from console
 * @return string
 */
function read_console() {
    return trim(fgets(STDIN));
}
/**
 * write message to standard output stream
 * @param $string
 */
function write_console($string) {
    fwrite(STDOUT, $string);
}