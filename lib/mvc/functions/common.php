<?php
use info\InfoCollector,
    \Exception;

// -----------------------  SYSTEM MESSAGES -----------------------------------//
/**
 * get message
 * @return string
 */
function __message($message, $param = null, $textdomain = null) {

    if (defined('APPLICATION_LANG') && extension_loaded('gettext')) {
        $textdomain = !$textdomain ? 'default' : $textdomain;
        if ($textdomain == 'default') {
            $message = dcgettext('default', $message);
        } else {
            $message = gettext($message);
        }
    }
    if (empty($param)) {
        return $message;
    }
    $command = '$message = sprintf($message,';
    foreach ($param as $value) {
        $value = str_replace('"', '\"', $value);
        $command .= '"' . $value . '",';
    }
    $command = substr($command, 0, -1);
    $command .= ");";
    eval($command);
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
    $array = @array_flip($array);
    unset($array['']);
    return array_flip($array);
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
 * @param array $data_map
 * @param array $column_array
 */
function filter_array($data_map, $column_array) {
    if(empty($data_map) || !is_array($column_array)) return array();
    return array_intersect_key($data_map, array_flip($column_array));
}
/**
 * get specified data from data
 * @param array $array_list
 * @param array $column_list
 */
function array_get_column($array_list, $column_list) {
    if (empty($column_list)) return $array;
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
//-----------------------  ENCODING -----------------------------------//

/**
 * convert string into utf-8 encoding
 * @param string $string
 * @return string
 */
function convert2utf8($string) {
    if (empty($string)) return '';
    $now_encode = mb_detect_encoding($string, "auto", true);
    if ($now_encode == 'UTF-8') {
        return $string;
    }
    if (extension_loaded('iconv')) {
        return iconv($now_encode, 'UTF-8', $string);
    }
    return mb_convert_encoding($string, 'UTF-8', $now_encode);

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
 * @return mixed
 *          false: failed
 *          string: file_name maded
 */
function make_file($base_dir, $type = 'monthly') {

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
    if (!file_exists($sub_folder)) {
        @mkdir($sub_folder, 0777, true);
        @chmod($sub_folder, 755);
    }
    $full_path = $sub_folder . '/' . $file_name;
    @file_put_contents($full_path, '', FILE_APPEND);
    if (!file_exists($full_path)) {
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
function directory_make_usable($dir, $chmod = '0744') {
    // If it doesn't exist, and can't be made
    if(!is_dir($dir) && !mkdir($dir, $chmod, true)) return false;
    // If it isn't writable, and can't be made writable
    if(!is_writable($dir) && !chmod($dir, $chmod)) return false;
    return true;
}

//-----------------------  CRYPT -----------------------------------//

/**
 * decode encoded data
 * @param string $crpty_value
 * @return mixed null if decode failed
 */
function decode_crypt_value($crpty_value, $crypt_key) {
    if (!function_exists('mcrypt_module_open')) return false;
    if (!function_exists('mcrypt_generic_init')) return false;
    $crpty_value = hex2bin($crpty_value);
    $iv = strrev($crypt_key);
    $td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
    mcrypt_generic_init($td, $crypt_key, $iv);
    $crpty_value = mdecrypt_generic($td, $crpty_value);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return utf8_encode(trim($crpty_value));
}

/**
 * make encode string
 * @param string $string
 * @param string $crypt_key
 * @return string encoded data
 */
function get_crypt_value($string, $crypt_key) {
    if (!function_exists('mcrypt_module_open')) return false;
    if (!function_exists('mcrypt_generic_init')) return false;
    $blocksize = 16;
    $pad = $blocksize - (strlen($string) % $blocksize);
    $string .= str_repeat(chr($pad), $pad);
    $iv = strrev($crypt_key);
    $td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
    mcrypt_generic_init($td, $crypt_key, $iv);
    $string = mcrypt_generic($td, $string);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return bin2hex($string);
}
/**
 * hex -> binary
 * @param string $hexdata
 */
function hex2bin($hexdata) {
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
 */
function get_random_string($length) {
    $str = '';
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
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