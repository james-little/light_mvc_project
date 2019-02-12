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
 *
 * Kasasi
 * =======================================================
 * convert kanjis to hira/kana/alphabet
 * or do morphological analysis
 * https://github.com/kokukuma/php-kakasi
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

use lightmvc\exception\AppException;
use lightmvc\exception\ExceptionCode;

class Kasasi
{

    /**
     * convert
     * return type should be specified like:
     *
     * example:
     *     $text = '狩野タツヤくん';
     *     $result = Kasasi::convert($text);
     *     print_r($result);
     * return
     *     hira => "かのたつやくん",
     *     kata => "カノタツヤクン",
     *     alph => "kanotatsuyakun",
     *
     * @param string $str
     * @param int $return_type
     * @return array
     * @throws AppException
     */
    public static function convert($str, $is_encode = true)
    {
        if (!extension_loaded('kakasi')) {
            throw new AppException('kakasi not loaded', ExceptionCode::WORD_SPLIT_EXTENSION_NOT_LOAD);
        }
        if (empty($str)) {
            return [];
        }
        $wordset        = KAKASI_CONVERT($str);
        $result         = [];
        $result['hira'] = $wordset->hira;
        $result['kata'] = $wordset->kata;
        if ($is_encode) {
            $result = self::encode($result);
        }
        $result['alph'] = $wordset->alph;
        return $result;
    }
    /**
     * split japanese sentences
     * example:
     *     $text = '私はラーメン好きです';
     *     $result = Kasasi::split($text);
     *     print_r($result);
     * return
     *     array('私', 'は', 'ラーメン', '好き', 'です')
     *
     * @param  string  $str
     * @param  boolean $is_encode
     * @return array
     */
    public static function split($str, $is_encode = true)
    {
        if (!extension_loaded('kakasi')) {
            throw new AppException('kakasi not loaded', ExceptionCode::WORD_SPLIT_EXTENSION_NOT_LOAD);
        }
        if (empty($str)) {
            return [];
        }
        $result_list = KAKASI_MORPHEME($str);
        if (empty($result_list)) {
            return [];
        }
        // kill duplicate items
        $result_list = array_flip(array_flip($result_list));
        if ($is_encode) {
            $result_list = self::encode($result_list);
        }
        return $result_list;
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
