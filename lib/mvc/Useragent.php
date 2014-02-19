<?php

/**
 * UserAgent
 *
 * @author koketsu <jameslittle.private@gmail.com>
 */
class Useragent {

    protected static $_carrier;
    protected static $_input_encoding_php;
    protected static $_output_encoding_php;
    protected static $_input_encoding_w3c;
    protected static $_output_encoding_w3c;

    const CARRIER_DOCOMO   = 'docomo';
    const CARRIER_SOFTBANK = 'softbank';
    const CARRIER_AU       = 'au';
    const CARRIER_ANDROID  = 'android';
    const CARRIER_IPHONE  = 'iphone';
    const CARRIER_IPAD = 'ipad';
    const CARRIER_IPOD = 'ipod';

    const TYPE_UNKNOWN = 'unknown';
    const TYPE_FEATURE_PHONE = 'featurephone';
    const TYPE_SMART_PHONE = 'smartphone';
    const PHP = 'php';

    const W3C = 'w3c';

    /**
     * get carrier of client
     * @return string carrier
     */
    public static function getCarrier() {
        if (self::$_carrier === null) self::detectCarrier();
        return self::$_carrier;
    }

    /**
     * get client type
     * @return string Useragent::TYPE_
     */
    public static function getTerminalType() {
        switch (self::getCarrier()) {
        case self::CARRIER_ANDROID:
        case self::CARRIER_IPHONE:
            return self::TYPE_SMART_PHONE;
        case self::CARRIER_DOCOMO:
        case self::CARRIER_SOFTBANK:
        case self::CARRIER_AU:
            return self::TYPE_FEATURE_PHONE;
        default:
            return self::TYPE_UNKNOWN;
        }
    }

    /**
     * Judge if the client is a smartphone
     * @return boolean
     */
    public static function isSmartPhone() {
        return self::getTerminalType() === self::TYPE_SMART_PHONE;
    }

    /**
     * Judge if the client is a feature phone
     * @return boolean
     */
    public static function isFeaturePhone() {
        return self::getTerminalType() === self::TYPE_FEATURE_PHONE;
    }

    /**
     * get input encoding
     * @param string $target
     * Useragent::PHP<br/>
     * Useragent::W3C
     * @return string
     */
    public static function getInputEncoding($target = self::PHP) {
        if (self::$_carrier === null) self::detectCarrier();
        return ($target === self::PHP) ? self::$_input_encoding_php : self::$_input_encoding_w3c;
    }

    /**
     * get output encoding
     * @param string $targe : Useragent::PHP<br/>, Useragent::W3C
     * @return string
     */
    public static function getOutputEncoding($target = self::PHP) {
        if (self::$_carrier === null) self::detectCarrier();
        return ($target === self::PHP) ? self::$_output_encoding_php : self::$_output_encoding_w3c;
    }
    /**
     * get raw user agent
     * @return string
     */
    public static function getRawUserAgent() {
    	return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }
	/**
	 * detect carrier 
	 */
    protected static function detectCarrier() {
        // judge carrier by user agent
        $ua = self::getRawUserAgent();
        if (preg_match("/^DoCoMo/i", $ua)) {
            self::$_carrier = self::CARRIER_DOCOMO;
        } else if (preg_match("/^(J\-PHONE|Vodafone|MOT\-[CV]|SoftBank)/i", $ua)) {
            self::$_carrier = self::CARRIER_SOFTBANK;
        } else if (preg_match("/^KDDI\-/i", $ua)) {
            self::$_carrier = self::CARRIER_AU;
        } else if(preg_match("/^Mozilla\/.*Android/i", $ua)) {
            self::$_carrier = self::CARRIER_ANDROID;
        } else if(preg_match("/^Mozilla\/.*iPhone/i", $ua)) {
            self::$_carrier = self::CARRIER_IPHONE;
        } else if(preg_match("/^Mozilla\/.*iPad/i", $ua)) {
            self::$_carrier = self::CARRIER_IPAD;
        } else if(preg_match("/^Mozilla\/.*iPod/i", $ua)) {
            self::$_carrier = self::CARRIER_IPOD;
        } else {
            self::$_carrier =  'default';
        }

        if (self::$_carrier === self::CARRIER_DOCOMO ||
            self::$_carrier === self::CARRIER_AU) {
            self::$_input_encoding_php = 'SJIS-win';
            self::$_output_encoding_php = 'SJIS-win';
            self::$_input_encoding_w3c = 'Shift_JIS';
            self::$_output_encoding_w3c = 'Shift_JIS';
        } else {
            self::$_input_encoding_php = 'UTF-8';
            self::$_output_encoding_php = 'UTF-8';
            self::$_input_encoding_w3c = 'UTF-8';
            self::$_output_encoding_w3c = 'UTF-8';
        }
    }
}
