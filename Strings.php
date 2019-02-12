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
 * when you want to create a wizard in your app.
 * first get a instance of the Wizard class or you can extend this to create your
 * own wizard class
 * ==============================================================================
 * string
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

class Strings
{
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
    public static function convertEncode($string, $from_encode, $to_encode)
    {
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
    public static function convert2UTF8($string, $from_encode)
    {
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
    public static function convertEncodingName($encode)
    {
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
