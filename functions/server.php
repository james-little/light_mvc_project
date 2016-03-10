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
        'REQUEST_URI'     => '',
    );
    $_SERVER = array_merge($default_server_values, $_SERVER);
    // Fix for IIS when running with PHP ISAPI
    if (empty($_SERVER['REQUEST_URI']) || (php_sapi_name() != 'cgi-fcgi' && preg_match('/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE']))) {
        // IIS Mod-Rewrite
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
        }
        // IIS Isapi_Rewrite
        elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
        } else {
            // Use ORIG_PATH_INFO if there is no PATH_INFO
            if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO'])) {
                $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
            }
            // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
            if (isset($_SERVER['PATH_INFO'])) {
                if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
                    $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                } else {
                    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
                }

            }
        }
    }
    // Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
    if (isset($_SERVER['SCRIPT_FILENAME']) && (strpos($_SERVER['SCRIPT_FILENAME'], 'php.cgi') == strlen($_SERVER['SCRIPT_FILENAME']) - 7)) {
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
    }

    // Fix for Dreamhost and other PHP as CGI hosts
    if (strpos($_SERVER['SCRIPT_NAME'], 'php.cgi') !== false) {
        unset($_SERVER['PATH_INFO']);
    }

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
        header("HTTP/1.1 404 Not Found Error");
        break;
    case 'forbidden':
        header("HTTP/1.1 403 Forbidden");
        break;
    case 'internal_error':
        header("HTTP/1.0 500 Internal Server Error");
        break;
    case 'unauthorized_invalid':
        header("HTTP/1.0 401 Unauthorized");
        header('Authorization: X-WARABI-TOKEN error="invalid_token"');
        break;
    case 'unauthorized_empty':
        header("HTTP/1.0 401 Unauthorized");
        header('Authorization: X-WARABI-TOKEN realm="warabi-token"');
        break;
    case 'unauthorized_expired':
        header("HTTP/1.0 401 Unauthorized");
        header('Authorization: X-WARABI-TOKEN realm="warabi" error_description="The access token expired"');
        break;
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
        return;
    }
    if ($data) {
        if ($is_compress) {
            $compress_level = !$compress_level ? 5 : $compress_level;
            $data           = gzcompress($data, $compress_level);
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
        echo_pro($data);
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
        (stripos($_SERVER['HTTP_USER_AGENT'], 'Shockwave') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') !== false);
}
/**
 * redirect to url
 * @param  string $url
 * @param  array $params
 * @return void
 */
function redirect($url, $params = null) {
    $url = add_get_params_to_url($url, $params);
    header('Location:' . $url);
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
        if ($ip_long === false) {
            $ip_long = 0;
        }

        $ip_list[] = $ip_long;
    }
    $headers = get_all_headers();
    // for proxies
    if (isset($headers['X-Real-IP'])) {
        $ip_long = ip2long($headers['X-Real-IP']);
        if ($ip_long === false) {
            $ip_long = 0;
        }

        $ip_list[] = $ip_long;
    }
    return $ip_list;
}
/**
 * get all header
 * @return array
 */
function get_all_headers() {
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    if (!is_array($_SERVER) && !count($_SERVER)) {
        return [];
    }
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $name           = substr($name, 5);
            $name           = strtolower(str_replace('_', ' ', $name));
            $name           = ucwords($name);
            $name           = str_replace(' ', '-', $name);
            $headers[$name] = $value;
        } elseif ($name == "CONTENT_TYPE") {
            $headers["Content-Type"] = $value;
        } elseif ($name == "CONTENT_LENGTH") {
            $headers["Content-Length"] = $value;
        } elseif ($name == "WARABI-TOKEN") {
            $headers["WARABI-TOKEN"] = $value;
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
    $query_param_list     = array();
    if (!empty($parsed_url_part_list['query'])) {
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
    if (count($query_param_list)) {
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
    $query_param_list     = array();
    if (!empty($parsed_url_part_list['query'])) {
        parse_str($parsed_url_part_list['query'], $query_param_list);
    }
    if (!empty($param_list)) {
        $query_param_list = array_merge($param_list, $query_param_list);
    }
    $url = $parsed_url_part_list['scheme'] . '://' . $parsed_url_part_list['host'];
    if (!empty($parsed_url_part_list['path'])) {
        $url .= $parsed_url_part_list['path'];
    }
    if (!empty($query_param_list)) {
        $url .= '?' . http_build_query($query_param_list);
    }
    return $url;
}
/**
 * url safe base64 encode
 * @param string $str
 * @return mixed
 */
function url_safe_base64_encode($str) {
    $find    = array('+', '/');
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
 * check email
 * @param string $email
 * @return bool
 */
function validate_email($email) {
    if (!is_string($email)) {
        return false;
    }
    $is_valid = true;
    $at_index = strrpos($email, "@");
    if (is_bool($at_index) && !$at_index) {
        $is_valid = false;
    } else {
        $domain     = substr($email, $at_index + 1);
        $local      = substr($email, 0, $at_index);
        $local_len  = strlen($local);
        $domain_len = strlen($domain);
        if ($local_len < 1 || $local_len > 64) {
            // local part length exceeded
            $is_valid = false;
        } elseif ($domain_len < 1 || $domain_len > 255) {
            // domain part length exceeded
            $is_valid = false;
        } elseif ($local[0] == '.' || $local[$local_len - 1] == '.') {
            // local part starts or ends with '.'
            $is_valid = false;
        } elseif (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $is_valid = false;
        } elseif (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $is_valid = false;
        } elseif (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $is_valid = false;
        } elseif (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
            str_replace("\\\\", "", $local))) {
            // character not valid in local part unless
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
                $is_valid = false;
            }
        }
        if ($is_valid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
            // domain not found in DNS
            $is_valid = false;
        }
    }
    return $is_valid;
}
/**
 * regularize url
 * @param  String $url
 * @return String | null
 */
function url_regularize($url) {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) {
        return null;
    }
    if (mb_strlen($url) != strlen($url)) {
        $url = mb_decode_numericentity($url, array(0x0, 0x2FFFF, 0, 0xFFFF), 'UTF-8');
    }
    $url   = trim($url);
    $regex = '#&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);#i';
    $url   = preg_replace($regex, '$1', $url);
    $url   = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) {
        return null;
    }
    return $url;
}
/**
 * current url
 */
function current_url() {
    $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
/**
 * current host
 */
function current_host() {
    $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
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
 * @param string $crypt_iv
 * @return mixed
 */
function get_cookie_pro($key, $crypt_key = null, $iv = null) {

    if (!isset($_COOKIE[$key])) {
        return false;
    }
    $cookie_value = $_COOKIE[$key];
    if ($crypt_key && $iv) {
        // Decrypt cookie
        $cookie_value = decode_mcrypt_value($cookie_value, $crypt_key, $iv);
    }
    return $cookie_value;
}
/**
 * Called before any output is sent to create an encrypted cookie with the given value.
 *
 * @param string $key cookie name
 * @param mixed $value to save
 * @param string $crypt_key
 * @param string $crypt_iv
 * @param int $expires: seconds from current time
 * @param string $path
 * @param string $domain
 * return boolean
 */
function set_cookie_pro($key, $value, $crypt_key = null, $iv = null, $expires = null, $path = '/', $domain = null) {

    $expires = is_null($expires) ? 0 : time() + $expires;
    $domain  = is_null($domain) ? getenv('HTTP_HOST') : $domain;
    if ($crypt_key && $iv) {
        $value = get_mcrypt_value($value, $crypt_key, $iv);
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
    set_cookie_pro($key, null, null, null, 1, $path, $domain);
}
/**
 * clear all cookies
 */
function clear_all_cookies() {
    if (!isset($_COOKIE)) {
        return;
    }
    foreach ($_COOKIE as $key => $value) {
        unset($_COOKIE[$key]);
        set_cookie_pro($key, null, null, null, 1);
    }
}
