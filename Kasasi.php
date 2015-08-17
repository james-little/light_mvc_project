<?php

/**
 * Kasasi
 * =======================================================
 * convert kanjis to hira/kana/alphabet
 * or do morphological analysis
 * https://github.com/kokukuma/php-kakasi
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

use exception\ExceptionCode,
    exception\AppException;

class Kasasi {


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
    public static function convert($str, $is_encode = true) {
        if(!extension_loaded('kakasi')) {
            throw new AppException('kakasi not loaded', ExceptionCode::WORD_SPLIT_EXTENSION_NOT_LOAD);
        }
        if(empty($str)) {
            return array();
        }
        $wordset = KAKASI_CONVERT($str);
        $result = array();
        $result['hira'] = $wordset->hira;
        $result['kata'] = $wordset->kata;
        if($is_encode) {
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
    public static function split($str, $is_encode = true) {
        if(!extension_loaded('kakasi')) {
            throw new AppException('kakasi not loaded', ExceptionCode::WORD_SPLIT_EXTENSION_NOT_LOAD);
        }
        if(empty($str)) {
            return array();
        }
        $result_list = KAKASI_MORPHEME($str);
        if(empty($result_list)) {
            return array();
        }
        // kill duplicate items
        $result_list = array_flip(array_flip($result_list));
        if($is_encode) {
            $result_list = self::encode($result_list);
        }
        return $result_list;
    }

    /**
     * encode
     * @param array $word_list
     * @return array
     */
    public static function encode($word_list) {
        if(empty($word_list)) {
            return $word_list;
        }
        foreach ($word_list as $key => $word) {
            $word_list[$key] = base64_encode($word);
        }
        return $word_list;
    }

}