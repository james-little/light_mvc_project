<?php
namespace context;

use context\Context;
use exception\AppException;
use exception\ExceptionCode;

/**
 * Runtime Context
 * =======================================================
 * Runtime context
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class RuntimeContext extends Context {

    const ENVI_PRODUCTION = 'production';
    const ENVI_DEVELOP    = 'develop';
    const ENVI_STAGING    = 'staging';

    const MODE_NORMAL = 'n';
    const MODE_CLI    = 'c';

    // application run environment
    protected static $APP_ENVI;
    // application run mode
    protected static $APP_RUN_MODE;
    // set is application debug mode
    protected static $IS_DEBUG_MODE = false;

    /**
     * constructor
     */
    protected function __construct() {
        parent::__construct();
        $this->getAppRunmode();
    }
    /**
     * get application runtime enviornment
     * @param string $cli_manual_envi: specify custom environment when run in cli mode
     * @return string
     */
    public function getAppEnvi($cli_manual_envi = null) {

        if ($cli_manual_envi &&
            in_array($cli_manual_envi, ['production', 'staging', 'develop']) &&
            $this->getAppRunmode() == self::MODE_CLI) {
            $this->setAppEnvi($cli_manual_envi);
        } else {
            $this->setAppEnvi();
        }
        if (!self::$APP_ENVI || !in_array(self::$APP_ENVI, ['production', 'staging', 'develop'])) {
            throw new AppException('set application environment failed', ExceptionCode::APP_ENVI_NOT_DEFINED);
        }
        defined('APPLICATION_ENVI') ? null : define('APPLICATION_ENVI', self::$APP_ENVI);
        return self::$APP_ENVI;
    }
    /**
     * get application run mode
     */
    public function getAppRunmode() {

        if (self::$APP_RUN_MODE) {
            return self::$APP_RUN_MODE;
        }
        if (defined('PHP_SAPI') && PHP_SAPI == 'cli') {
            self::$APP_RUN_MODE = self::MODE_CLI;
        } else {
            self::$APP_RUN_MODE = self::MODE_NORMAL;
        }
        return self::$APP_RUN_MODE;
    }
    /**
     * set application environment
     */
    protected function setAppEnvi($envi_manual = null) {}
}