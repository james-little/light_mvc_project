<?php

/**
 * string
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class String {

    const ENCODE_UTF32     = 1;
    const ENCODE_UTF16     = 2;
    const ENCODE_UTF8      = 3;
    const ENCODE_ASCII     = 4;
    const ENCODE_EUCJP     = 5;
    const ENCODE_SJIS      = 6;
    const ENCODE_ISO8859_1 = 7;
    const ENCODE_BIG5      = 8;
    const ENCODE_GB18030   = 9;
    const ENCODE_GBK       = 10;

    /**
     * convert encoding
     * @param  string $string
     * @param  int $from_encode
     * @param  int $to_encode
     * @return string
     */
    public static function convertEncode($string, $from_encode, $to_encode) {
        if (empty($string)) {
            return '';
        }
        $from_encode = self::convertEncodingName($from_encode);
        $to_encode   = self::convertEncodingName($to_encode);

        if (!$from_encode || !$to_encode) {
            return null;
        }
        if (extension_loaded('iconv')) {
            return iconv($from_encode, $to_encode . '//IGNORE', $string);
        }
        return mb_convert_encoding($string, 'UTF-8', $from_encode);
    }
    /**
     * convert encoding to utf8
     * @param  string $string
     * @param  int $from_encode
     * @param  int $to_encode
     * @return string
     */
    public static function convert2UTF8($string, $from_encode) {
        if (empty($string)) {
            return '';
        }
        if ($from_encode == self::ENCODE_UTF8) {
            return $string;
        }
        $from_encode = self::convertEncodingName($from_encode);
        return self::convertEncode($string, $from_encode, self::ENCODE_UTF8);
    }
    /**
     * convert encode to encoding name
     * @param  int $encode
     * @return string
     */
    public static function convertEncodingName($encode) {
        switch ($encode) {
        case self::ENCODE_UTF32:
            return 'UTF-32';
        case self::ENCODE_UTF16:
            return 'UTF-16';
        case self::ENCODE_UTF8:
            return 'UTF-8';
        case self::ENCODE_ASCII:
            return 'ASCII';
        case self::ENCODE_EUCJP:
            return 'EUC-JP';
        case self::ENCODE_SJIS:
            return 'SJIS';
        case self::ENCODE_ISO8859_1:
            return 'ISO-8859-1';
        case self::ENCODE_BIG5:
            return 'BIG-5';
        case self::ENCODE_GB18030:
            return 'GB18030';
        case self::ENCODE_GBK:
            return 'GBK';
        }
        return '';
    }
}