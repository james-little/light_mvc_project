<?php
use info\InfoCollector;

//-----------------------  SERVER VARS -----------------------------------//
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
function send_http_response($type, $data = null) {
    switch ($type) {
        case 'file_not_found':
            header("HTTP/1.1 404 Not Found Error");   break;
        case 'forbidden':
            header("HTTP/1.1 403 Forbidden");         break;
        case 'json':
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data);
            break;
        case 'text':
            header('Content-Type: text/plain; charset=utf-8');
            echo convert2utf8($data);
            break;
        default:
            break;
    }
}

/**
 * Returns whether this is an AJAX (XMLHttpRequest) request.
 * @return boolean whether this is an AJAX (XMLHttpRequest) request.
 */
function get_is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
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

function get_real_ip_list() {
    $ip_list = array();
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip_list[] = $_SERVER['REMOTE_ADDR'];
    }
    if(function_exists('getallheaders')) {
        $headers = getallheaders();
        // for proxies
        if (isset($headers['X-Real-IP'])) {
            $ip_list[] = $headers['X-Real-IP'];
        }
    }
    return $ip_list;
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
 * check url
 * @param string $url
 */
function validate_url($url) {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if ($url) {
        return $url;
    }
    $url = urldecode($url) . ' ';
    if (mb_strlen($url) !== strlen($url)) {
        $url = mb_decode_numericentity($url, array(0x0, 0x2FFFF, 0, 0xFFFF), 'UTF-8');
    }
    $url = trim($url);
    $regex = '#&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);#i';
    $url = preg_replace($regex, '$1', htmlentities($url, ENT_QUOTES, 'UTF-8'));
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if ($url) {
        return $url;
    }
    return false;
}
/**
 * current url
 */
function current_url() {
    $protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https')  === false ? 'http' : 'https';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'utf-8');
}

//-----------------------  COOKIE -----------------------------------//
/**
 * get cookie with secured data
 * @param string $key of cookie
 * @param string $crypt_key
 * @param int $timeout: linux timestamp
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
 * @param int $expires: linux timestamp
 * @param string $path
 * @param string $domain
 * return boolean
 */
function set_cookie_pro($key, $value, $crypt_key = null, $expires = null, $path = '/', $domain = null) {

    if (empty($value)) return false;
    $expires = is_null($expires) ? 0 : $expires;
    $domain = is_null($domain) ? getenv('HTTP_HOST') : $domain;
    if ($crypt_key) {
        $value = get_crypt_value($value, $crypt_key);
    }
    __add_info(__message('set to cookie: %s, %s, %s, %s, %s', array($key, $value, $expires, $path, $domain)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
    return setcookie($key, $value, $expires, $path, $domain);
}