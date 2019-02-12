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
 * Rain View
 * =======================================================
 * implementation of view interface using rain template engine
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\view;

require dirname(FRAMEWORK_ROOT_DIR) . DIRECTORY_SEPARATOR . 'raintpl/inc/rain.tpl.class.php';

use lightmvc\Encoding;
use lightmvc\exception\ExceptionCode;
use lightmvc\exception\ViewException;
use lightmvc\info\InfoCollector;
use RainTPL;
use lightmvc\view\ViewInterface;

class RainView extends RainTPL implements ViewInterface
{

    private $cache_expire_time;
    private $is_cache_enabled;
    protected $_output_encode = Encoding::ENCODE_UTF8;

    /**
     * initialization
     *   tpl_dir           : template directory
     *   base_url          : base url
     *   cache_dir         : cache directory
     *   path_replace      : path replace
     *   path_replace_list : path replace list
     *   tpl_ext           : template extension
     */
    public function init($config = null)
    {

        $allowed_settings = [
            'tpl_dir', 'cache', 'base_url', 'output_encode',
            'tpl_ext', 'path_replace', 'black_list',
        ];
        if (empty($config)) {
            return;
        }
        foreach ($config as $key => $value) {
            if (empty($value) || !in_array_pro($key, $allowed_settings)) {
                continue;
            }
            switch ($key) {
                case 'path_replace':
                    self::configure($key, $value['enabled']);
                    if ($value['enabled']) {
                        self::configure('path_replace_list', $value['list']);
                    }
                    break;
                case 'tpl_ext':
                    self::configure('tpl_ext', empty($value) ? 'tpl' : $value);
                    break;
                case 'cache':
                    $this->is_cache_enabled  = empty($value['enabled']) ? false : true;
                    $this->cache_expire_time = empty($value['expire_time']) ? 3600 : $value['expire_time'];
                    self::configure('cache_dir', empty($value['dir']) ? 'tmp/' : $value['dir']);
                    break;
                case 'output_encode':
                    $this->_output_encode = $value;
                    break;
                default:
                    self::configure($key, $value);
                    break;
            }
            // message
            __add_info('view initialized: ' . $key, InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
        // enable php
        self::$php_enabled = true;
    }
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::getOutputEncode()
     */
    public function getOutputEncode()
    {
        return $this->_output_encode;
    }
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::assign()
     */
    public function assignVar($key, $value, $is_by_reference = true)
    {
        if ($is_by_reference) {
            parent::assignByRef($key, $value);
            return ;
        }
        parent::assign($key, $value);
    }
    /**
     * clear cache
     */
    public function clearCache()
    {
        array_map("unlink", glob(self::$cache_dir . '*.rtpl.php'));
    }
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param = null)
    {

        $template_file = preg_replace('#\.' . self::$tpl_ext . '$#', '', $template_file);
        if ($this->is_cache_enabled) {
            $output = $this->cache($template_file, $this->cache_expire_time);
        } else {
            if (empty($template_file)) {
                throw new ViewException('template file not exist', ExceptionCode::VIEW_TEMPLATE_NOT_FOUND);
            }
            // assign template var
            if (!empty($template_var)) {
                foreach ($template_var as $key => $value) {
                    $this->assignVar($key, $value);
                }
            }
            $output = $this->draw($template_file, true);
        }
        if ($this->_output_encode != Encoding::ENCODE_UTF8) {
            $output = Encoding::convertEncode(
                $output,
                Encoding::ENCODE_UTF8,
                $this->_output_encode
            );
        }
        return $output;
    }
}
