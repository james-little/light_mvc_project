<?php

/**
 * Robbe
 * =======================================================
 * use robbe to split chinese string
 * http://git.oschina.net/lionsoul/robbe
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

use exception\ExceptionCode,
    exception\AppException;

class Robbe {


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
    public static function split($str, $is_encode = true) {
        if(!extension_loaded('robbe')) {
            throw new AppException('robbe not loaded', ExceptionCode::WORD_SPLIT_EXTENSION_NOT_LOAD);
        }
        if (rb_charset() != 'UTF-8' ) {
            throw new AppException('robbe charset is not utf-8', ExceptionCode::WORD_SPLIT_CHARSET_NOT_MATCH);
        }
        if(empty($str)) {
            return array();
        }
        // return items
        // word: RB_RET_WORD, type: RB_RET_TYPE, length: RB_RET_LENGTH true path: RB_RET_RLEN, ret off: RB_RET_OFF
        // $rargs = RB_RET_TYPE | RB_RET_LEN | RB_RET_RLEN | RB_RET_OFF | RB_RET_POS;
        $result_list = rb_split($str, array('mode' => RB_CMODE), RB_RET_WORD);
        if(empty($result_list)) {
            return array();
        }
        $word_list = array();
        foreach ($result_list as $result) {
            $result['word'] = trim($result['word']);
            if(empty($result['word'])) {
                continue ;
            }
            $word_list[] = $result['word'];
        }
        // kill duplicate items
        $word_list = array_flip(array_flip($word_list));
        if($is_encode) {
            $word_list = self::encode($word_list);
        }
        return $word_list;
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