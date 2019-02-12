<?php
/**
 *  Copyright 2016 Koketsu.
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
 * ==============================================================================
 * url filter
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 *
 * @version 1.0
 **/
namespace lightmvc;

class Url
{
    /**
     * make clean url.
     *
     * @param url $url
     *
     * @return Ambigous <string, mixed>
     */
    public static function cleanUrl($url)
    {
        $parsed_url_part_list = parse_url($url);
        $query_param_list = [];
        if (!empty($parsed_url_part_list['query'])) {
            parse_str($parsed_url_part_list['query'], $query_param_list);
        }
        if (count($query_param_list)) {
            foreach ($query_param_list as $query_param_key => $query_param_value) {
                $query_param_list[$query_param_key] = urlencode($query_param_value);
            }
        }
        $url = $parsed_url_part_list['scheme'].'://'.$parsed_url_part_list['host'];
        if (!empty($parsed_url_part_list['path'])) {
            $url .= urlencode($parsed_url_part_list['path']);
            $url = preg_replace('#%2F#', '/', $url);
        }
        if (count($query_param_list)) {
            $url .= '?'.http_build_query($query_param_list);
        }

        return $url;
    }

    /**
     * add get params to url.
     *
     * @param string $url
     * @param array  $param_list
     */
    public static function addGetParamsToUrl($url, $param_list)
    {
        if (empty($url) || empty($param_list)) {
            return $url;
        }
        $parsed_url_part_list = parse_url($url);
        $query_param_list = [];
        if (!empty($parsed_url_part_list['query'])) {
            parse_str($parsed_url_part_list['query'], $query_param_list);
        }
        if (!empty($param_list)) {
            $query_param_list = array_merge($param_list, $query_param_list);
        }
        $url = $parsed_url_part_list['scheme'].'://'.$parsed_url_part_list['host'];
        if (!empty($parsed_url_part_list['path'])) {
            $url .= $parsed_url_part_list['path'];
        }
        if (!empty($query_param_list)) {
            $url .= '?'.http_build_query($query_param_list);
        }

        return $url;
    }

    /**
     * check url.
     *
     * @param string $url
     */
    public static function validateUrl($url)
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if ($url) {
            return $url;
        }
        $url = urldecode($url).' ';
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
}
