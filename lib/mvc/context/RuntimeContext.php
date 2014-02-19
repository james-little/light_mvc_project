<?php
namespace context;

use context\Context,
    exception\AppException;
/**
 * Runtime Context
 * =======================================================
 * Runtime context
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
abstract class RuntimeContext extends Context {

    protected static $_instance;

    const ENVI_PRODCTION = 'production';
    const ENVI_DEVELOP = 'develop';
    const ENVI_STAGING = 'staging';

    const MODE_NORMAL = 'n';
    const MODE_CLI = 'c';

    // application run environment
    protected static $APP_ENVI;
    // application run mode
    protected static $APP_RUN_MODE;
    // set is application debug mode
    protected static $IS_DEBUG_MODE;

    /**
     * constructor
     */
    protected function __construct(){}

    /**
     * singleton
     * @return RuntimeContext
     */
    public static function getInstance(){
        if(!static::$_instance){
            static::$_instance = new static();
        }
        return static::$_instance;
    }
    /**
     * get application runtime enviornment
     */
    public function getAppEnvi() {
        $this->setAppEnvi();
        if (!self::$APP_ENVI || !in_array(self::$APP_ENVI, array('production', 'staging', 'develop'))) {
            throw new AppException(__message('set application environment failed'));
        }
        defined('APPLICATION_ENVI') ? null : define('APPLICATION_ENVI', self::$APP_ENVI);
        return self::$APP_ENVI;
    }
    /**
     * set is enable information collector
     */
    public function setIsInfoCollectorEnabled() {
    	global $argv;
    	if ($this->getAppRunmode() == self::MODE_CLI && !empty($argv[3])) {
    		defined('ENABLE_INFO_COLLECT') ? null : define('ENABLE_INFO_COLLECT', (bool) $argv[3]);
    	} else {
    		if (isset($_REQUEST['is_enable_ic'])) {
    			defined('ENABLE_INFO_COLLECT') ? null : define('ENABLE_INFO_COLLECT', (bool) $_REQUEST['is_enable_ic']);
    		}
    	}
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
     * get is debug mode
     */
    public function getIsDebugMode() {
        if (self::$IS_DEBUG_MODE) {
            return self::$IS_DEBUG_MODE;
        }
        global $argv;
        if ($this->getAppRunmode() == self::MODE_CLI && !empty($argv[2])) {
            self::$IS_DEBUG_MODE = (bool) $argv[2];
        } else {
            self::$IS_DEBUG_MODE = empty($_REQUEST['is_debug']) ? false : true;
        }
        return self::$IS_DEBUG_MODE;
    }
    /**
     * set application environment
     */
    abstract protected function setAppEnvi();
}