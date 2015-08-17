<?php
/**
 * UserAgent
 *
 * @author koketsu <jameslittle.private@gmail.com>
 */
class Useragent {

    private static $carrier;
    private static $brower;
    private static $brower_version;

    const CARRIER_DEFAULT   = 1;
    const CARRIER_DOCOMO   = 2;
    const CARRIER_SOFTBANK = 3;
    const CARRIER_AU       = 4;
    const CARRIER_ANDROID  = 5;
    const CARRIER_IPHONE  = 6;
    const CARRIER_IPAD = 7;
    const CARRIER_IPOD = 8;

    const TYPE_PC = 1;
    const TYPE_FEATURE_PHONE = 2;
    const TYPE_SMART_PHONE = 3;

    const BROWSER_IE = 1;
    const BROWSER_CHROME = 2;
    const BROWSER_SAFARI = 3;
    const BROWSER_FIREFOX = 4;
    const BROWSER_OPERA = 5;
    const BROWSER_NETSCAPE = 6;
    const BROWSER_OTHER = 7;

    /**
     * get carrier of client
     * @return string carrier
     */
    public static function getCarrier() {
        if (self::$carrier === null) {
            self::detectCarrier();
        }
        return self::$carrier;
    }

    /**
     * get browser of client
     * @return int browser
     */
    public static function getBrowser() {
        if (self::$brower === null) {
            self::detectBrowser();
        }
        return self::$brower;
    }

    /**
     * get browser of client
     * @return int browser
     */
    public static function getBrowserVersion() {
        if (self::$brower_version === null) {
            self::detectBrowser();
        }
        return self::$brower_version;
    }

    /**
     * get client type
     * @return string Useragent::TYPE_
     */
    public static function getTerminalType() {
        $carrier = self::getCarrier();
        switch ($carrier) {
            case self::CARRIER_ANDROID:
            case self::CARRIER_IPHONE:
                return self::TYPE_SMART_PHONE;
            case self::CARRIER_DOCOMO:
            case self::CARRIER_SOFTBANK:
            case self::CARRIER_AU:
                return self::TYPE_FEATURE_PHONE;
            default:
                return self::TYPE_PC;
        }
    }

    /**
     * Judge if the client is a smartphone
     * @return boolean
     */
    public static function isSmartPhone() {
        return self::getTerminalType() == self::TYPE_SMART_PHONE;
    }

    /**
     * Judge if the client is a feature phone
     * @return boolean
     */
    public static function isFeaturePhone() {
        return self::getTerminalType() === self::TYPE_FEATURE_PHONE;
    }
    /**
     * get raw user agent
     * @return string
     */
    public static function getRawUserAgent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
    }
    /**
     * detect carrier
     */
    private static function detectCarrier() {
        // judge carrier by user agent
        $ua = self::getRawUserAgent();
        if(preg_match("/^Mozilla\/.*Android/i", $ua)) {
            self::$carrier = self::CARRIER_ANDROID;
        } else if(preg_match("/^Mozilla\/.*iPhone/i", $ua)) {
            self::$carrier = self::CARRIER_IPHONE;
        } else if(preg_match("/^Mozilla\/.*iPad/i", $ua)) {
            self::$carrier = self::CARRIER_IPAD;
        } else if(preg_match("/^Mozilla\/.*iPod/i", $ua)) {
            self::$carrier = self::CARRIER_IPOD;
        } else if (preg_match("/^DoCoMo/i", $ua)) {
            self::$carrier = self::CARRIER_DOCOMO;
        } else if (preg_match("/^(J\-PHONE|Vodafone|MOT\-[CV]|SoftBank)/i", $ua)) {
            self::$carrier = self::CARRIER_SOFTBANK;
        } else if (preg_match("/^KDDI\-/i", $ua)) {
            self::$carrier = self::CARRIER_AU;
        } else {
            self::$carrier =  self::CARRIER_DEFAULT;
        }
        if (self::$carrier === self::CARRIER_DOCOMO ||
            self::$carrier === self::CARRIER_AU) {
            Application::setEncodingConfig(array('input' => 'Shift_JIS', 'output' => 'Shift_JIS'));
        }
    }
    /**
     * detect browser type
     */
    private static function detectBrowser() {
        $ua = self::getRawUserAgent();
        $tmp = array();
        if(preg_match("/MSIE ([0-9]+)/i", $ua, $tmp)) {
            self::$brower = self::BROWSER_IE;
            self::$brower_version = $tmp[1];
        } else if(preg_match("/Firefox\/([0-9]+)/i", $ua, $tmp)) {
            self::$brower = self::BROWSER_FIREFOX;
            self::$brower_version = $tmp[1];
        } else if(preg_match("/Chrome\/([0-9]+)/i", $ua, $tmp)) {
            self::$brower = self::BROWSER_CHROME;
            self::$brower_version = $tmp[1];
        } else if(preg_match("/Safari\/([0-9]+)/i", $ua, $tmp)) {
            self::$brower = self::BROWSER_SAFARI;
            self::$brower_version = $tmp[1];
        } else if(preg_match("/Opera\/([0-9]+)/i", $ua, $tmp)) {
            self::$brower = self::BROWSER_OPERA;
            self::$brower_version = $tmp[1];
        } else if(preg_match("/Netscape[0-9]*\/([0-9]+)/i", $ua, $tmp)) {
            self::$brower = self::BROWSER_NETSCAPE;
            self::$brower_version = $tmp[1];
        } else {
            self::$brower = self::BROWSER_OTHER;
            self::$brower_version = 0;
        }
    }
}
