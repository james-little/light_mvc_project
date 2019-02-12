<?php

/**
 *  Copyright 2016 Koketsu
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
 * Robbe
 * use robbe to split chinese string
 * https://code.google.com/p/robbe/wiki/RobbeFunctions
 *
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;

class Robbe
{

    /**
     * split
     * return type should be specified like:
     *     Robbe::RETURN_TYPE_WORD | Robbe::RB_RET_LEN
     * example:
     *     $text = 'i like u very much';
     *     $split_array = Robbe::split($text);
     *     print_r($split_array);
     *
     * @param string $str
     * @param int $return_type
     * @throws AppException
     */
    public static function split($str, $is_encode = true)
    {
        if (!extension_loaded('robbe')) {
            throw new AppException('robbe not loaded', ExceptionCode::WORD_SPLIT_EXTENSION_NOT_LOAD);
        }
        if (rb_charset() != 'UTF-8') {
            throw new AppException('robbe charset is not utf-8', ExceptionCode::WORD_SPLIT_CHARSET_NOT_MATCH);
        }
        if (empty($str)) {
            return [];
        }
        // return items
        // word: RB_RET_WORD, type: RB_RET_TYPE, length: RB_RET_LENGTH true path: RB_RET_RLEN, ret off: RB_RET_OFF
        // $rargs = RB_RET_TYPE | RB_RET_LEN | RB_RET_RLEN | RB_RET_OFF | RB_RET_POS;
        $result_list = rb_split($str, ['mode' => RB_CMODE], RB_RET_WORD);
        if (empty($result_list)) {
            return [];
        }
        $word_list = [];
        foreach ($result_list as $result) {
            $result['word'] = trim($result['word']);
            if (empty($result['word'])) {
                continue;
            }
            $word_list[] = $result['word'];
        }
        // kill duplicate items
        $word_list = array_flip(array_flip($word_list));
        if ($is_encode) {
            $word_list = self::encode($word_list);
        }
        return $word_list;
    }

    /**
     * encode
     * @param array $word_list
     * @return array
     */
    public static function encode($word_list)
    {
        if (empty($word_list)) {
            return $word_list;
        }
        foreach ($word_list as $key => $word) {
            $word_list[$key] = base64_encode($word);
        }
        return $word_list;
    }
}
