<?php
/**
 * ClassLoader
 * =======================================================
 * class loader for autoload class.
 *
 * Rules:
 *    1. %app_dir% with full namespaced class name
 *    2. %framework_dir% with full namespaced class name
 *    3. %include_dir% with full namespaced class name
 *
 * Example:
 *    $class = new \logic\adReward\communicator\Communicator();
 *    file_path: APP_DIR . '/logic/adReward/communicator/Communicator.php'
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

class ClassLoader {

    private static $_class_map = array();
    private static $class_path_map = array();
    private static $use_include_path = false;
    private static $scan_path = array();

    /**
     * __construct
     */
    public function __construct() {
        spl_autoload_register(array($this, 'autoload'));
    }
    /**
     * Turns on searching the include path for class files.
     * @param bool $user_include_path
     */
    public function setUseIncludePath($user_include_path) {
        self::$use_include_path = (bool) $user_include_path;
    }
    /**
     * Can be used to check if the autoloader uses the include path to check
     * for classes.
     * @return bool
     */
    public function getUseIncludePath() {
        return self::$use_include_path;
    }
    /**
     * add scan dir
     * @param string $scan_path
     */
    public function addScanPath($scan_path) {
        if (file_exists($scan_path)) {
            self::$scan_path[$scan_path] = '';
        }
    }
    /**
     * remove scan path
     * @param string $scan_path
     */
    public function removeScanPath($scan_path) {
        if (array_key_exists($scan_path, self::$scan_path)) {
            unset(self::$scan_path);
        }
    }
    /**
     * use clone to load class
     * @param string $class
     */
    public static function loadClass($class) {

        $file_path = self::getClassFilePath($class);
        if ($file_path === false) {
            return false;
        }
        if (empty(self::$_class_map[$file_path])) {
            self::$_class_map[$file_path] = new $class();
        }
        return clone self::$_class_map[$file_path];
    }
    /**
     * clear cache
     * @param string | null $class class_name(namespace included)
     */
    public static function clearCache($class = null) {

        if ($class === null) {
            self::$_class_map = array();
            return ;
        }
        $file_path = self::getClassFilePath($class);
        if ($file_path === false) {
            return ;
        }
        if (array_key_exists($file_path, self::$_class_map[$file_path])) {
            unset(self::$_class_map[$file_path]);
        }
    }
    /**
     * Autoloader
     */
    public function autoload($class) {

//         if(isset(self::$class_path_map[$class])) {
//             require_once self::$class_path_map[$class];
//             return true;
//         }
        if (class_exists($class)) {
            return true;
        }
        $file_path = self::getClassFilePath($class);
        if($file_path === false) {
            return false;
        }
//         self::$class_path_map[$class] = $file_path;
        require_once $file_path;
        return true;
    }
    /**
     * Convert class name to file path
     */
    protected static function getClassFilePath($class) {

        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }
        $file_name = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $scan_dirs = array_keys(self::$scan_path);
        // scan include path
        if (self::$use_include_path) {
            $inclue_path_dirs = explode(PATH_SEPARATOR, ini_get('include_path'));
            $scan_dirs = array_merge($scan_dirs, $inclue_path_dirs);
            unset($inclue_path_dirs);
        }
        foreach ($scan_dirs as $scan_dir) {
            $path = $scan_dir . $file_name;
            if (file_exists($path)) {
                return $path;
            }
        }
        return false;
    }
}