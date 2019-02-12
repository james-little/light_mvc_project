<?php

use lightmvc\info\InfoCollector;
use lightmvc\Strings;
use lightmvc\Application;

// -----------------------  SYSTEM MESSAGES -----------------------------------//
/**
 * i18n translate string
 * @param string $message
 * @param array $param
 * @return string
 */
function __message($message, $param = null, $textdomain = null)
{
    if (empty($message)) {
        return '';
    }
    if (!extension_loaded('gettext')) {
        return $message;
    }
    if (defined('APPLICATION_LANG') && APPLICATION_LANG != Application::LANG_EN) {
        if ($textdomain) {
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
    $message,
    $type = InfoCollector::TYPE_LOGIC,
    $level = InfoCollector::LEVEL_INFO
) {
    if ($type != InfoCollector::TYPE_EXCEPTION) {
        if (defined('ENABLE_INFO_COLLECT') && ENABLE_INFO_COLLECT === false) {
            return;
        }
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
function __exception_message(Exception $e)
{
    return $e->getFile() . ':' . $e->getLine() . ' :: '
    . $e->getMessage();
}

// -----------------------  ARRAY -----------------------------------//
/**
 * clear empty, null or duplicated value in array
 * @param array $array
 * @return array
 */
function array_clear_empty($array)
{
    if (!is_array($array)) {
        return $array;
    }
    $array = array_filter($array, function ($val) {
        return $val !== null && $val !== '';
    });
    return $array;
}
/**
 * improved in_array function
 * @param mixed $value
 * @param array $array
 * @return boolean
 */
function in_array_pro($value, $array)
{
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
 *     $column_list = array('a', 'c');
 *     filter_array($data_map, $column_list);
 *
 *     output:
 *         array('a' => 1, 'c' => 3)
 * @param array $data_map
 * @param array $column_list
 */
function filter_array($data_map, $column_list = [], $not_empty = false)
{
    if (empty($data_map) || !is_array($column_list)) {
        return [];
    }
    if (empty($column_list)) {
        return $data_map;
    }
    $array = array_intersect_key($data_map, array_flip($column_list));
    if ($not_empty === false) {
        return $array;
    }
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
 *     $column_list = array('a', 'c');
 *     array_get_column($data_map, $column_list);
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
function array_get_column($array_list, $column_list)
{
    if (empty($column_list)) {
        return $array_list;
    }
    $data_list = [];
    foreach ($array_list as $key => $array) {
        $data_list[$key] = filter_array($array, $column_list);
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
function array_get_column_value($array_list, $column, $enable_duplicate = false)
{
    if (empty($array_list)) {
        return [];
    }
    if (empty($column) || !is_string($column)) {
        return [];
    }
    $data_list = array_column($array_list, $column);
    if (!$enable_duplicate) {
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
function make_multi_val_string($data)
{
    if (empty($data)) {
        return '';
    }

    if (is_string($data) || is_numeric($data)) {
        // is $data is string then make it into array
        $data = explode(',', $data);
    }
    $data = array_clear_empty($data);
    if (empty($data)) {
        return '';
    }

    $data = array_unique($data);
    sort($data, SORT_NUMERIC);
    return implode(',', $data);
}
/**
 * @example
 *     given array:
 *         $records = array(
 *           array('id' => 2135, 'first_name' => 'John'),
 *           array('id' => 2131, 'first_name' => 'Sally'),
 *           array('id' => 2137, 'first_name' => 'Peter'),
 *          );
 *          $data_list = order_by($records, 'id');
 *
 *      result would be:
 *          array(2135,2131,2137)
 * @param array $array_list
 * @param string $column
 * @return array
 */
function order_by($array_list, $column, $order = 'asc')
{
    if (empty($array_list)) {
        return [];
    }
    $is_sort_column_numeric = is_numeric(current($array_list)[$column]);
    $sort_key_list          = [];
    foreach ($array_list as $index => &$array) {
        $sort_key_list[$array[$column]] = $array;
    }
    if ($order) {
        $order = strtolower($order);
    }
    if ($is_sort_column_numeric) {
        if ($order && $order == 'desc') {
            krsort($sort_key_list, SORT_NUMERIC);
        } else {
            ksort($sort_key_list, SORT_NUMERIC);
        }
    } else {
        if ($order && $order == 'desc') {
            krsort($sort_key_list, SORT_STRING);
        } else {
            ksort($sort_key_list, SORT_STRING);
        }
    }
    $result_list = [];
    foreach ($sort_key_list as $array) {
        array_push($result_list, $array);
    }
    return $result_list;
}
// -----------------------  STRING -----------------------------------//
/**
 * convert var to string
 * @param mixed $val
 * @return string
 */
function convert_string($val)
{
    if (is_string($val)) {
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
function _serialize($val)
{
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
function _unserialize($val)
{
    if (function_exists('igbinary_unserialize')) {
        $val = igbinary_unserialize($val);
    } else {
        $val = unserialize($val);
    }
    return $val;
}
/**
 * merge string
 * put str1 into even position and str2 into odd position char by char
 * example:
 *     $str1 = 'abcde';
 *     $str2 = 'defghij';
 *
 *     $merged_str = 'adbecfdgehij';
 *
 * @param  string $str1
 * @param  string $str2
 * @return string
 */
function merge_string($str1, $str2)
{
    if (empty($str1) && empty($str2)) {
        return '';
    }
    if (empty($str1)) {
        return $str2;
    }
    if (empty($str2)) {
        return $str1;
    }
    $len1       = strlen($str1);
    $len2       = strlen($str2);
    $max        = $len1 >= $len2 ? $len2 * 2 : $len1 * 2;
    $merged_str = '';
    for ($i = 0; $i < $max; $i++) {
        if ($i % 2 == 0) {
            $merged_str .= $str1[intval($i / 2)];
        } else {
            $merged_str .= $str2[intval($i / 2)];
        }
    }
    if ($len1 >= $len2) {
        $merged_str .= substr($str1, $len2);
    } else {
        $merged_str .= substr($str2, $len1);
    }
    return $merged_str;
}
/**
 * split merged string back to str1 and str2
 * @param  string $str
 * @param  int $len1
 * @return array
 */
function split_string($str, $len1)
{
    $str_array = [0 => '', 1 => ''];
    if (empty($str)) {
        return $str_array;
    }
    if (!$len1) {
        $str_array[1] = $str;
        return $str_array;
    }
    $len2 = strlen($str) - $len1;
    $max  = $len1 >= $len2 ? $len2 * 2 : $len1 * 2;
    for ($i = 0; $i < $max; $i++) {
        if ($i % 2 == 0) {
            $str_array[0] .= $str[$i];
        } else {
            $str_array[1] .= $str[$i];
        }
    }
    if ($len1 >= $len2) {
        $str_array[0] .= substr($str, $max);
    } else {
        $str_array[1] .= substr($str, $max);
    }
    return $str_array;
}
/**
 * check is chinese
 * @param  string  $str
 * @return bool
 */
function is_chinese_str($str)
{
    if (empty($str)) {
        return false;
    }
    if (preg_match("/[\x{4e00}-\x{9fa5}]+/u", $str)) {
        return true;
    }
    return false;
}
/**
 * check is japanese
 * @param  string  $str
 * @return bool
 */
function is_japanese_str($str)
{
    if (empty($str)) {
        return false;
    }
    if (preg_match("/[ぁ-んー]+/u", $str)) {
        // ひらがな
        return true;
    } elseif (preg_match("/[ァ-ヶー]+/u", $str)) {
        // カタカナ
        return true;
    } elseif (preg_match("/[一-龠]+/u", $str)) {
        // 漢字
        return true;
    }
    return false;
}
/**
 * check if the string is json
 * @param  string  $str
 * @return bool
 */
function is_json($str)
{
    if (empty($str)) {
        return false;
    }
    if (!is_string($str)) {
        return false;
    }
    if (is_array(json_decode($str, true)) && json_last_error() == 0) {
        return true;
    }
    return false;
}
/**
 * camelize string
 * @param  string  $string
 * @param  string  $delimiter
 * @param  boolean $is_capitalize_first
 * @return string
 */
function camelize($string, $delimiter = '_', $is_capitalize_first = true)
{
    $str = str_replace($delimiter, '', ucwords($string, $delimiter));
    if (!$is_capitalize_first) {
        $str = lcfirst($str);
    }
    return $str;
}
/**
 * convert cameel case string to underscore case
 * @param  string $string
 * @param  string $delimiter
 * @return string
 */
function from_camelcase($string, $delimiter = '_')
{
    return ltrim(strtolower(preg_replace('/[A-Z]/', $delimiter . '$0', $string)), $delimiter);
}

//-----------------------  ENCODING -----------------------------------//

/**
 * parse csv file to array
 * @param string $filename
 * @param string $delimiter
 * @return array
 */
function csv_to_array($file_name, $delimiter = ',')
{
    $handle = @fopen($file_name, 'r');
    if ($handle === false) {
        return [];
    }
    $data = [];
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
function make_file($base_dir, $type = 'monthly', $is_make_file = true)
{
    if (!in_array($type, array('daily', 'monthly', 'hourly', 'weekly'))) {
        return false;
    }
    $file_name  = '';
    $sub_folder = '';
    switch ($type) {
        case 'monthly':
            $sub_folder = $base_dir;
            $file_name  = date('Ym') . '.log';
            break;
        case 'weekly':
            $sub_folder = "{$base_dir}/" . date('Ym');
            $file_name  = date('YW') . '.log';
            break;
        case 'daily':
            $sub_folder = "{$base_dir}/" . date('Ym');
            $file_name  = date('Ymd') . '.log';
            break;
        case 'hourly':
            $sub_folder = "{$base_dir}/" . date('Ym');
            $file_name  = date('YmdH') . '.log';
            break;
    }
    if (!is_dir($sub_folder)) {
        @mkdir($sub_folder, 0777, true);
        @chmod($sub_folder, 0777);
    }
    $full_path = $sub_folder . '/' . $file_name;
    if (!$is_make_file) {
        return $full_path;
    }
    @file_put_contents($full_path, '', FILE_APPEND);
    if (!is_file($full_path)) {
        return $file_name;
    }
    return $full_path;
}

/**
 * mkdir recursive
 * @param  string  $path
 * @param  int     $mode
 * @return bool
 */
function mkdir_r($path, $mode = 0755)
{
    $dirs = explode(DIRECTORY_SEPARATOR, $path);
    $dir  = '';
    foreach ($dirs as $part) {
        $dir .= $part . DIRECTORY_SEPARATOR;
        if (is_dir($dir) === false && strlen($dir) > 0) {
            if (@mkdir($dir, $mode) === false) {
                return false;
            } else {
                chmod($dir, $mode);
            }
        }
    }
    return true;
}
/**
 * delete folder files below
 * @param  string  $dir
 * @param  boolean $is_delete_self
 * @return void
 */
function del_dir_files($dir)
{
    if (!is_dir($dir)) {
        return;
    }
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $fullpath = $dir . '/' . $file;
        if (is_dir($fullpath)) {
            continue;
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
function del_dir_r($dir, $is_delete_self = false)
{
    if (!is_dir($dir)) {
        return;
    }
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $fullpath = $dir . '/' . $file;
        if (is_dir($fullpath)) {
            del_dir_r($fullpath, true);
        } else {
            unlink($fullpath);
        }
    }
    closedir($dh);
    if ($is_delete_self) {
        rmdir($dir);
    }
}
/**
 * path info for multiple-byte file path string
 * @param  string $path
 * @param  int $options
 * @return array
 */
function mb_pathinfo($path, $options = null)
{
    $path  = urlencode($path);
    $parts = null === $options ? pathinfo($path) : pathinfo($path, $options);
    foreach ($parts as $field => $value) {
        $parts[$field] = urldecode($value);
    }
    return $parts;
}
/**
 * chmod recursively
 * @param  string $path
 * @param  int $mode
 * @return
 */
function chmod_r($path, $mode = 0755)
{
    $dir = new DirectoryIterator($path);
    foreach ($dir as $item) {
        if ($item->isDot()) {
            continue;
        }
        if ($item->isDir()) {
            chmod_r($item->getPathname(), $mode);
        }
        @chmod($item->getPathname(), $mode);
    }
}
/**
 * change owner recursively
 * @param  string $path
 * @param  int $owner
 */
function chown_r($path, $owner)
{
    $dir = new DirectoryIterator($path);
    foreach ($dir as $item) {
        if ($item->isDot()) {
            continue;
        }
        if ($item->isDir()) {
            chown_r($item->getPathname(), $owner);
        }
        @chown($item->getPathname(), $owner);
    }
}
/**
 * change group recursively
 * @param  string $path
 * @param  int $group
 */
function chgrp_r($path, $group)
{
    $dir = new DirectoryIterator($path);
    foreach ($dir as $item) {
        if ($item->isDot()) {
            continue;
        }
        if ($item->isDir()) {
            chgrp_r($item->getPathname(), $group);
        }
        @chgrp($item->getPathname(), $group);
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
function decode_crypt_value($crypt_value, $crypt_key, $base64_encode = false)
{
    if (!function_exists('mcrypt_module_open')) {
        return null;
    }

    if (!function_exists('mcrypt_generic_init')) {
        return null;
    }
    if ($base64_encode) {
        $crypt_value = base64_decode($crypt_value);
    } else {
        if (phpversion() < '5.4.0') {
            $crypt_value = hex2bin5_3($crypt_value);
        } else {
            if (strlen($crypt_value) % 2 > 0) {
                return null;
            }
            $crypt_value = hex2bin($crypt_value);
        }
    }
    if (!$crypt_value) {
        return null;
    }
    if (!$crypt_key) {
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
function get_crypt_value($string, $crypt_key, $base64_encode = false)
{
    if (!function_exists('mcrypt_module_open')) {
        return null;
    }

    if (!function_exists('mcrypt_generic_init')) {
        return null;
    }

    if (!$crypt_key) {
        return null;
    }
    $blocksize = 16;
    $pad       = $blocksize - (strlen($string) % $blocksize);
    $string .= str_repeat(chr($pad), $pad);
    $iv = strrev($crypt_key);
    $td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv);
    mcrypt_generic_init($td, $crypt_key, $iv);
    $string = mcrypt_generic($td, $string);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    if ($base64_encode) {
        return base64_encode($string);
    }
    return bin2hex($string);
}
/**
 * decode encoded data
 * @param string $encrypt_value
 * @param string $crypt_key
 * @param string $iv
 * @return mixed null if decode failed
 */
function decode_mcrypt_value($encrypt_value, $crypt_key, $iv)
{
    if (!function_exists('mcrypt_decrypt')) {
        return null;
    }
    if (!$encrypt_value) {
        return null;
    }
    if (!$crypt_key || !$iv) {
        return null;
    }
    $encrypt_value = base64_decode($encrypt_value);
    $decrypted     = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $crypt_key, $encrypt_value, MCRYPT_MODE_CBC, $iv), "\r\n\0 ");
    $decrypted     = preg_replace('/[\x00-\x1F]/', '', $decrypted);
    return $decrypted;
}

/**
 * make encode string
 * @param string $string
 * @param string $crypt_key
 * @param boolean $iv
 * @return string encoded data
 */
function get_mcrypt_value($string, $crypt_key, $iv)
{
    if (!function_exists('mcrypt_decrypt')) {
        return null;
    }
    if (!$crypt_key || !$iv) {
        return null;
    }
    $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $crypt_key, $string, MCRYPT_MODE_CBC, $iv);
    return base64_encode($encrypted);
}

/**
 * hex -> binary
 * @param string $hexdata
 */
function hex2bin5_3($hexdata)
{
    $bindata = '';
    $len     = strlen($hexdata);
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
function get_random_string($length, $with_symbol = false)
{
    $str   = '';
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
function token()
{
    mt_srand(random_int(0 - PHP_INT_MAX, PHP_INT_MAX));
    return md5(str_shuffle(chr(mt_rand(32, 126)) . uniqid() . microtime(true)));
}

//-----------------------  DATE -----------------------------------//
/**
 * check if $date1 is before $date2
 * @param string $date1
 * @param string $date2
 * @return bool
 */
function is_before($date1, $date2)
{
    return strtotime($date1) <= strtotime($date2);
}
/**
 * format to UTC
 * @param number | string $date
 */
function format_2_UTC($date, $delimiter = '/')
{
    // Get the default timezone
    $default_tz = date_default_timezone_get();
    // Set timezone to UTC
    date_default_timezone_set('UTC');
    // convert datetime into UTC
    if (is_string($date)) {
        $date = strtotime($date);
    }
    $utc_format = date("Y{$delimiter}m{$delimiter}d\TG:i:s\Z", $date);
    // Might not need to set back to the default but did just in case
    date_default_timezone_set($default_tz);
    return $utc_format;
}
/**
 * get week date
 * get date string with specified weekday of first/second/third/fourth week of
 * months of year
 * --------------------------
 * example
 * get the date string of the sunday of the first week of Apirl, 2016
 *
 * $weekday_date = get_week_date(1, 0, 4, 2016);
 *
 * @param  int $num
 * @param  int $weekday
 * @param  int $month
 * @param  int $year
 * @return string
 */
function get_week_date($num, $weekday, $month, $year)
{
    $weekday_name = get_weekday_name($weekday);
    if (is_null($weekday_name)) {
        return null;
    }
    if ($num > 5) {
        return null;
    }
    $num_text = null;
    switch ($num) {
        case 1:
            $num_text = 'first';
            break;
        case 2:
            $num_text = 'second';
            break;
        case 3:
            $num_text = 'third';
            break;
        case 4:
            $num_text = 'fourth';
            break;
    }
    $month_text = date('F', mktime(0, 0, 0, $month, 10));
    $dt         = new DateTime("{$num_text} {$weekday_name} of {$month_text} {$year}");
    return $dt->format('Y-m-d');
}
/**
 * get weekday text name by numeric weekday number
 * @param  int $weekday
 * @return string
 */
function get_weekday_name($weekday)
{
    $name = null;
    switch ($weekday) {
        case 0:
            $name = 'Sunday';
            break;
        case 1:
            $name = 'Monday';
            break;
        case 2:
            $name = 'Tuesday';
            break;
        case 3:
            $name = 'Wednesday';
            break;
        case 4:
            $name = 'Thursday';
            break;
        case 5:
            $name = 'Friday';
            break;
        case 6:
            $name = 'Saturday';
            break;
    }
    return $name;
}
/**
 * check date
 * @param string $date
 * @param string $format
 * @return bool
 */
function validate_date($date_str, $format = null)
{
    $d = null;
    try {
        if ($format) {
            $d = DateTime::createFromFormat($format, $date_str);
        } else {
            $d = new DateTime($date_str);
        }
    } catch (Exception $e) {
        return false;
    }
    if (!$d) {
        return false;
    }
    if ($format) {
        return $d->format($format) == $date_str;
    }
    $format    = 'Y-m-d';
    $date_str1 = $d->format($format);
    if ($date_str1 != $date_str) {
        return false;
    }
    $d = DateTime::createFromFormat($format, $date_str1);
    return $d->format($format) == $date_str1;
}
/**
 * check datetime string
 * @param string $datetime
 * @param string $format
 * @return bool
 */
function validate_datetime($datetime_str, $format = null)
{
    $d = null;
    try {
        if ($format) {
            $d = DateTime::createFromFormat($format, $datetime_str);
        } else {
            $d = new DateTime($datetime_str);
        }
    } catch (Exception $e) {
        return false;
    }
    if (!$d) {
        return false;
    }
    if ($format) {
        return $d->format($format) == $datetime_str;
    }
    $format = 'Y-m-d H:i';
    if (substr_count($datetime_str, ':') > 1) {
        $format = 'Y-m-d H:i:s';
    }
    $datetime_str1 = $d->format($format);
    if ($datetime_str1 != $datetime_str) {
        return false;
    }
    $d = DateTime::createFromFormat($format, $datetime_str1);
    return $d->format($format) == $datetime_str1;
}
/**
 * check timestamp number is valid
 *
 * @param int $timestamp
 * @return bool
 */
function validate_timestamp($timestamp)
{
    if (!is_numeric($timestamp)) {
        return false;
    }
    if (!$timestamp) {
        return false;
    }
    return date('Y-m-d H:i:s', $timestamp) !== false;
}
//-----------------------  NUMERIC -----------------------------------//
/**
 * check if value is between start and end
 * @param numeric $val
 * @param numeric $start
 * @param numeric $end
 * @return bool
 */
function between($val, $start, $end)
{
    if (!is_numeric($val)) {
        return false;
    }
    return $val >= $start && $val < $end;
}

//-----------------------  ECHO BIG STRING -----------------------------------//
/**
 * echo big string
 * @param string $string
 * @param int $buffer_size
 */
function echo_pro($string, $buffer_size = 8192)
{
    if (empty($string)) {
        return '';
    }
    $len = strlen($string);
    if ($len <= $buffer_size) {
        echo $string;
        return;
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
function read_console()
{
    return trim(fgets(STDIN));
}
/**
 * write message to standard output stream
 * @param $string
 */
function write_console($string)
{
    fwrite(STDOUT, $string);
}
