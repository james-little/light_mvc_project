<?php

/**
 * PinyinConv
 * =======================================================
 * convert chinese characters to pinyin
 * https://github.com/overtrue/pinyin
 *
 * Example
 *     1. convert into pinyin without accent
 *         $text = "带着希望去旅行，比到达终点更美好";
 *         $pinyin_text = PinyinConv::convert($text);
 *         // will echo
 *         // dai zhe xi wang qu lu xing bi dao da zhong dian geng mei hao
 *         echo $pinyin_text;
 *
 *     2. convert into pinyin with accent
 *         $text = "带着希望去旅行，比到达终点更美好";
 *         $pinyin_text = PinyinConv::convert($text, true);
 *         // will echo
 *         // dài zhe xī wàng qù lǔ xíng bǐ dào dá zhōng diǎn gèng měi hǎo
 *         echo $pinyin_text;
 *
 *     3. get frist lettters of each character
 *          $text = "带着希望去旅行，比到达终点更美好";
 *          // d z x w q l x b d d z d g m h
 *          $first_c_text = PinyinConv::getFirstLetter($text);
 *
 *     4. add user custom dictionary
 *          $text = '冷';
 *          $dic = array(
 *              '冷' => 're4'
 *          );
 *          PinyinConv::addCustomDic($dic);
 *          // rè
 *          $pinyin_str = PinyinConv::convert($text);
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

use Overtrue\Pinyin\Pinyin;

require dirname(FRAMEWORK_ROOT_DIR) . '/overtrue-pinyin/src/Pinyin/Pinyin.php';

class PinyinConv extends Pinyin {

    /**
     * convert chinese character into chinese pinyin
     * Example:
     *     $text = "你好世界";
     *     $pinyin_text = PinyinConv::convert($text);
     *     // will echo ni hao
     *     echo $pinyin_text;
     * @param  string  $text
     * @param  bool $with_accent
     * @return string
     */
    public static function convert($text, $with_accent = false) {
        if(empty($text)) {
            return '';
        }
        return parent::trans($text, array('accent' => false));
    }
    /**
     * get first letter of each character
     * @param  string $text
     * @return string
     */
    public static function getFirstLetter($text) {
        if(empty($text)) {
            return '';
        }
        return parent::letter($text);
    }
    /**
     * add user custom dic definition
     * @param array $custom_dic
     *        example: array(
     *            '冷' => 're4'
     *        )
     */
    public static function addCustomDic(array $custom_dic) {
        if(empty($custom_dic)) {
            return ;
        }
        return parent::appends($custom_dic);
    }
}