<?php
/**
 * Copyright 2016 Koketsu
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
 *
 * ClassLoader
 * =======================================================
 * class loader for autoload class.
 *
 * Rules:
 *    1. %app_dir% with full namespaced class name
 *    2. %framework_dir% with full namespaced class name
 *    3. %include_dir% with full namespaced class name
 *
 * @example
 *
 *     $class_loader = new ClassLoader();
 *     ClassLoader::addScanPath(%PROJECT_DIR%);
 *     $class = ClassLoader::loadClass('\user\logic\UserLogic');
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc;

class ClassLoader
{

    private static $_class_map       = [];
    private static $class_path_map   = [];
    private static $use_include_path = false;
    private static $scan_path        = [];

    /**
     * __construct
     */
    public function __construct()
    {
        spl_autoload_register(array($this, 'autoload'));
    }
    /**
     * Turns on searching the include path for class files.
     * @param bool $user_include_path
     */
    public static function setUseIncludePath($user_include_path)
    {
        self::$use_include_path = (bool) $user_include_path;
        $inclue_path_dirs       = explode(PATH_SEPARATOR, ini_get('include_path'));
        if (!empty($inclue_path_dirs)) {
            foreach ($inclue_path_dirs as $inclue_path_dir) {
                if (self::$use_include_path) {
                    self::addScanPath($inclue_path_dir);
                } else {
                    self::removeScanPath($inclue_path_dir);
                }
            }
        }
    }
    /**
     * Can be used to check if the autoloader uses the include path to check
     * for classes.
     * @return bool
     */
    public static function getUseIncludePath()
    {
        return self::$use_include_path;
    }
    /**
     * add scan dir
     * @param string $scan_path
     */
    public static function addScanPath($scan_path)
    {
        if (is_dir($scan_path)) {
            if ($scan_path == '.') {
                $scan_path = realpath($scan_path) . DIRECTORY_SEPARATOR;
            }
            self::$scan_path[$scan_path] = '';
        }
    }
    /**
     * remove scan path
     * @param string $scan_path
     */
    public static function removeScanPath($scan_path)
    {
        if (array_key_exists($scan_path, self::$scan_path)) {
            unset(self::$scan_path);
        }
    }
    /**
     * use clone to load class
     * @param string $class
     */
    public static function loadClass($class)
    {
        $file_path = self::getClassFilePath($class);
        if ($file_path === false) {
            return false;
        }
        if (empty(self::$_class_map[$file_path])) {
            self::$_class_map[$file_path] = new $class();
        }
        return self::$_class_map[$file_path];
    }
    /**
     * load module class
     * @param string $module
     * @param string $class
     */
    public static function loadModuleClass($module, $class)
    {
        $suffix = substr($class, -5);
        $package = null;
        switch ($suffix) {
            case 'Model':
                $package = 'model';
                break;
            case 'Logic':
                $package = 'logic';
                break;
            case 'Trait':
                $package = 'traits';
                break;
            default:
                if (substr($class, -10) == 'Controller') {
                    $package = 'controller';
                }
                break;
        }
        if (is_null($package)) {
            return false;
        }
        $className = "\\{$module}\\{$package}\\{$class}";
        return self::loadClass($className);
    }
    /**
     * clear cache
     * @param string | null $class class_name(namespace included)
     */
    public static function clearCache($class = null)
    {
        if ($class === null) {
            self::$_class_map = [];
            return;
        }
        $file_path = self::getClassFilePath($class);
        if ($file_path === false) {
            return;
        }
        if (array_key_exists($file_path, self::$_class_map[$file_path])) {
            unset(self::$_class_map[$file_path]);
        }
    }
    /**
     * Autoloader
     */
    public function autoload($class)
    {
        if (class_exists($class)) {
            return true;
        }
        $file_path = self::getClassFilePath($class);
        if ($file_path === false) {
            return false;
        }
        include $file_path;
        return true;
    }
    /**
     * Convert class name to file path
     */
    protected static function getClassFilePath($class)
    {
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }
        $file_name = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $path = FRAMEWORK_ROOT_DIR . $file_name;
        if (is_file($path)) {
            return $path;
        }
        foreach (self::$scan_path as $scan_dir => $val) {
            $path = $scan_dir . $file_name;
            if (is_file($path)) {
                return $path;
            }
        }
        return false;
    }
}
