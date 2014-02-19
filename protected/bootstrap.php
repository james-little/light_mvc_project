<?php
use context\RuntimeContext;

class AppRuntimeContext extends RuntimeContext {
    /**
     * set applicaton environment
     * @see context.RuntimeContext::setAppEnvi()
     */
    protected function setAppEnvi() {

        $application_context = isset($_SERVER['APPLICATION_ON']) ? $_SERVER['APPLICATION_ON']: null;
        $application_context = strtolower($application_context);
        switch ($application_context) {
            case 'production':
                self::$APP_ENVI = self::ENVI_PRODCTION;
                ini_set('display_errors', false);
                ini_set('display_startup_errors', false);
                ini_set('log_errors', true);
                defined('ENABLE_INFO_COLLECT') ? null : define('ENABLE_INFO_COLLECT', false);
                break;
            case 'staging':
                self::$APP_ENVI = self::ENVI_STAGING;
                ini_set('display_errors', false);
                ini_set('display_startup_errors', false);
                ini_set('log_errors', true);
                defined('ENABLE_INFO_COLLECT') ? null : define('ENABLE_INFO_COLLECT', true);
                break;
            default:
                self::$APP_ENVI = self::ENVI_DEVELOP;
                error_reporting(E_ALL | E_STRICT);
                ini_set('display_errors', true);
                ini_set('display_startup_errors', true);
                ini_set('log_errors', true);
                defined('ENABLE_INFO_COLLECT') ? null : define('ENABLE_INFO_COLLECT', true);
                break;
        }
    }
}
