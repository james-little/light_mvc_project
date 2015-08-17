<?php
use info\InfoCollector;

//-----------------------  SERVER VARS -----------------------------------//
/**
 * fix server vars
 * @return void
 */
function fix_server_vars() {
    $default_server_values = array(
        'SERVER_SOFTWARE' => '',
        'REQUEST_URI' => '',
    );
    $_SERVER = array_merge( $default_server_values, $_SERVER );
    // Fix for IIS when running with PHP ISAPI
    if (empty($_SERVER['REQUEST_URI'] ) || ( php_sapi_name() != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {
        // IIS Mod-Rewrite
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
        }
        // IIS Isapi_Rewrite
        else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
        } else {
            // Use ORIG_PATH_INFO if there is no PATH_INFO
            if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO']))
                $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
            // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
            if (isset( $_SERVER['PATH_INFO'])) {
                if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
                    $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                else
                    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
            }
        }
    }
    // Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
    if (isset($_SERVER['SCRIPT_FILENAME']) && (strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi') == strlen($_SERVER['SCRIPT_FILENAME']) - 7))
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
    // Fix for Dreamhost and other PHP as CGI hosts
    if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi' ) !== false)
        unset($_SERVER['PATH_INFO']);
}


//-----------------------  HTTP RESPONSE -----------------------------------//
/**
 * send http response
 * @param string $type
 * @param string $data
 * @param string $encode
 * @param bool $is_compress
 * @param int $compress_level
 * @return void
 */
function send_http_response($type, $data = null, $encode = null, $is_compress = false, $compress_level = null) {
    switch ($type) {
        case 'file_not_found':
            header("HTTP/1.1 404 Not Found Error");               break;
        case 'forbidden':
            header("HTTP/1.1 403 Forbidden");                     break;
        case 'internal_error':
            header("HTTP/1.0 500 Internal Server Error");         break;
        case 'json':
            header('Cache-Control: no-cache, must-revalidate');
            header("Content-Type: application/json; charset={$encode}");
            break;
        case 'text':
        case 'html':
            if ($type == 'text') {
                header("Content-Type: text/plain; charset={$encode}");
            } else {
                header("Content-Type: text/html; charset={$encode}");
            }
            break;
        default:
            return ;
    }
    if ($data) {
        if ($is_compress) {
            $compress_level = !$compress_level ? 5 : $compress_level;
            $data = gzcompress($data, $compress_level);
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
        }
        header('Content-Length: ' . strlen($data));
        __add_info(
            sprintf('send http response[size:%sk]: %s',
                strlen($data) / 1024,
                var_export($data, true)
            ),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG
        );
        echo $data;
    }
}

/**
 * Returns whether this is an AJAX (XMLHttpRequest) request.
 * @return boolean whether this is an AJAX (XMLHttpRequest) request.
 */
function get_is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

/**
 * Returns whether this is an Adobe Flash or Adobe Flex request.
 * @return boolean whether this is an Adobe Flash or Adobe Flex request.
 */
function get_is_flash_request() {
    return isset($_SERVER['HTTP_USER_AGENT']) &&
    (stripos($_SERVER['HTTP_USER_AGENT'],'Shockwave') !== false || stripos($_SERVER['HTTP_USER_AGENT'],'Flash') !== false);
}

//-----------------------  IP -----------------------------------//

/**
 * get real ip list
 * @return array
 */
function get_real_ip_list() {
    $ip_list = array();
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip_long = ip2long($_SERVER['REMOTE_ADDR']);
        if ($ip_long === false) $ip_long = 0;
        $ip_list[] = $ip_long;
    }
    $headers = get_all_headers();
    // for proxies
    if (isset($headers['X-Real-IP'])) {
        $ip_long = ip2long($headers['X-Real-IP']);
        if ($ip_long === false) $ip_long = 0;
        $ip_list[] = $ip_long;
    }
    return $ip_list;
}

/**
 * get all header
 * @return array
 */
function get_all_headers() {
    if(function_exists('getallheaders')) {
        return getallheaders();
    }
    if (!is_array($_SERVER)) {
        return array();
    }
    $headers = array();
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}

//-----------------------  URL -----------------------------------//

/**
 * get clean url
 * @param string $url
 * @return Ambigous <string, mixed>
 */
function url_clean($url) {

    $parsed_url_part_list = parse_url($url);
    $query_param_list = array();
    if(!empty($parsed_url_part_list['query'])) {
        parse_str($parsed_url_part_list['query'], $query_param_list);
    }
    if (count($query_param_list)) {
        foreach ($query_param_list as $query_param_key => $query_param_value) {
            $query_param_list[$query_param_key] = urlencode($query_param_value);
        }
    }
    $url = $parsed_url_part_list['scheme'] . '://' . $parsed_url_part_list['host'];
    if (!empty($parsed_url_part_list['path'])) {
        $url .= urlencode($parsed_url_part_list['path']);
        $url = preg_replace('#%2F#', '/', $url);
    }
    if(count($query_param_list)) {
        $url .= '?' . http_build_query($query_param_list);
    }
    return $url;
}

/**
 * add get params to url
 * @param string $url
 * @param array $param_list
 */
function add_get_params_to_url($url, $param_list) {

    if (empty($url) || empty($param_list)) {
        return $url;
    }
    $parsed_url_part_list = parse_url($url);
    $query_param_list = array();
    if(!empty($parsed_url_part_list['query'])) {
        parse_str($parsed_url_part_list['query'], $query_param_list);
    }
    if(!empty($param_list)) {
        $query_param_list = array_merge($param_list, $query_param_list);
    }
    $url = $parsed_url_part_list['scheme'] . '://' . $parsed_url_part_list['host'];
    if (!empty($parsed_url_part_list['path'])) {
        $url .= $parsed_url_part_list['path'];
    }
    if(!empty($query_param_list)) {
        $url .= '?' . http_build_query($query_param_list);
    }
    return $url;
}
/**
 * url safe base64 encode
 * @param string $str
 * @return mixed
 */
function url_safe_base64_encode($str)  {
    $find = array('+', '/');
    $replace = array('-', '_');
    return str_replace($find, $replace, base64_encode($str));
}
/**
 * check url
 * @param string $url
 * @return bool
 */
function validate_url($url) {
    $url = url_regularize($url);
    return $url === null ? false : true;
}
/**
 * regularize url
 * @param  String $url
 * @return String | null
 */
function url_regularize($url) {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if(!$url) {
        return null;
    }
    if (mb_strlen($url) !== strlen($url)) {
        $url = mb_decode_numericentity($url, array(0x0, 0x2FFFF, 0, 0xFFFF), 'UTF-8');
    }
    $url = trim($url);
    $regex = '#&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);#i';
    $url = preg_replace($regex, '$1', $url);
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if(!$url) {
        return null;
    }
    return $url;
}

/**
 * current url
 */
function current_url() {
    $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https')  === false ? 'http' : 'https';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
/**
 * Convert special characters to HTML entities
 * @param string $string
 *     example
 *     '&' (ampersand) -> '&amp;'
 *     '"' (double quote) -> '&quot;'
 *     "'" (single quote) -> '&#039;' when ENT_NOQUOTES is not set or &apos; only when ENT_QUOTES is set.
 *     '<' (less than) becomes '&lt;'
 *     '>' (greater than) becomes '&gt;'
 * @return string
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'utf-8');
}

//-----------------------  COOKIE -----------------------------------//
/**
 * get cookie with secured data
 * @param string $key of cookie
 * @param string $crypt_key
 * @return mixed
 */
function get_cookie_pro($key, $crypt_key = null) {

    if(!isset($_COOKIE[$key])) {
        return false;
    }
    $cookie_value = $_COOKIE[$key];
    if ($crypt_key) {
        // Decrypt cookie
        $cookie_value = decode_crypt_value($cookie_value, $crypt_key);
    }
    return $cookie_value;
}
/**
 * Called before any output is sent to create an encrypted cookie with the given value.
 *
 * @param string $key cookie name
 * @param mixed $value to save
 * @param string $crypt_key
 * @param int $expires: seconds from current time
 * @param string $path
 * @param string $domain
 * return boolean
 */
function set_cookie_pro($key, $value, $crypt_key = null, $expires = null, $path = '/', $domain = null) {

    $expires = is_null($expires) ? 0 : time() + $expires;
    $domain = is_null($domain) ? getenv('HTTP_HOST') : $domain;
    if ($crypt_key) {
        $value = get_crypt_value($value, $crypt_key);
    }
    __add_info(
        sprintf('set to cookie: %s, %s, %s, %s, %s', $key, $value, $expires, $path, $domain),
        InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG
    );
    return setcookie($key, $value, $expires, $path, $domain);
}
/**
 * unset cookie value
 * @param string $key
 * @param string $path
 * @param string $domain
 */
function unset_cookie_pro($key, $path = '/', $domain = null) {

    if (empty($key)) {
        return false;
    }
    if (!isset($_COOKIE[$key])) {
        return false;
    }
    unset($_COOKIE[$key]);
    set_cookie_pro($key, '', null, 1, $path, $domain);
}
/**
 * clear all cookies
 */
function clear_all_cookies() {
    if (!isset($_COOKIE)) {
        return ;
    }
    foreach($_COOKIE as $key => $value) {
        unset($_COOKIE[$key]);
        set_cookie_pro($key, '', null, 1);
    }
}
