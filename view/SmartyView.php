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
 * Smarty View
 * =======================================================
 * smarty implementation of view interface
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\view;

use lightmvc\exception\ExceptionCode;
use lightmvc\exception\ViewException;
use lightmvc\info\InfoCollector;
use Smarty;
use lightmvc\view\ViewInterface;
use lightmvc\Encoding;

require dirname(FRAMEWORK_ROOT_DIR) . DIRECTORY_SEPARATOR . 'Smarty/libs/Smarty.class.php';

class SmartyView extends Smarty implements ViewInterface
{

    protected $_output_encode = Encoding::ENCODE_UTF8;

    /**
     * construct
     */
    public function __construct()
    {
        // Class Constructor.
        // These automatically get set with each new instance.
        parent::__construct();
        $this->caching = Smarty::CACHING_LIFETIME_CURRENT;
    }

    /**
     * initialize
     * @param array $config
     * smarty config
     *   template_dir    : template directory
     *   compile_dir     : compile dir
     *   cache_dir       : cache dir
     *   config_dir      : configuration directory
     *   plugins_dir     : plugin directory
     */
    public function init($config = null)
    {
        $allowed_settings = [
            'template_dir', 'compile_dir', 'cache',
            'plugins_dir', 'left_delimiter',
            'right_delimiter', 'output_encode',
        ];
        $config = $config['settings'];
        if (empty($config)) {
            return;
        }
        foreach ($config as $key => $value) {
            if (empty($value) || !in_array_pro($key, $allowed_settings)) {
                continue;
            }
            switch ($key) {
                case 'cache':
                    $this->caching        = empty($value['enabled']) ? parent::CACHING_OFF : parent::CACHING_LIFETIME_SAVED;
                    $this->cache_lifetime = empty($value['expire_time']) ? 3600 : $value['expire_time'];
                    $cache_dir            = empty($value['dir']) ? TMP_DIR : $value['dir'];
                    $this->setCacheDir($cache_dir);
                    break;
                case 'output_encode':
                    $this->_output_encode = $value;
                    break;
                case 'compile_dir':
                    $this->setCompileDir($value);
                    break;
                case 'template_dir':
                    $this->setTemplateDir($value);
                    break;
                case 'plugins_dir':
                    $this->addPluginsDir($value);
                    break;
                default:
                    $this->$key = $value;
                    break;
            }
            // message
            __add_info(
                sprintf('view initialized: %s, %s', $key, var_export($value, true)),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
        }
        $this->php_handling = Smarty::PHP_ALLOW;
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
            $this->assignByRef($key, $value);
        } else {
            parent::assign($key, $value);
        }
    }

    /**
     * render template
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param = null)
    {
        if (empty($template_file)) {
            return '';
        }
        $is_template_exist = $this->checkIsTemplateExist($template_file);
        if (!$is_template_exist) {
            throw new ViewException(
                'view template not exists: ' . $template_file,
                ExceptionCode::VIEW_TEMPLATE_NOT_FOUND
            );
        }
        // cache id
        $cache_id = null;
        if (!$this->caching && !empty($cache_param['cache_id'])) {
            $cache_id = $cache_param['cache_id'];
        }
        // compile id
        $compile_id = null;
        if (!empty($cache_param['compile_id'])) {
            $compile_id = $cache_param['compile_id'];
        }
        // assign variables to template when not use cache or cache is not exists
        if (!$this->isCached($template_file, $cache_id, $compile_id)) {
            // assign template var
            if (!empty($template_var)) {
                foreach ($template_var as $key => $value) {
                    $this->assignVar($key, $value);
                }
            }
        }
        $this->muteExpectedErrors();
        $output = $this->fetch($template_file, $cache_id, $compile_id);
        if ($this->_output_encode != Encoding::ENCODE_UTF8) {
            $output = Encoding::convertEncode(
                $output,
                Encoding::ENCODE_UTF8,
                $this->_output_encode
            );
        }
        $this->unmuteExpectedErrors();
        return $output;
    }
    /**
     * check is smarty template exists
     * @param  string $template_file
     * @return bool
     */
    private function checkIsTemplateExist($template_file)
    {
        $template_dirs     = $this->getTemplateDir();
        $is_template_exist = false;
        foreach ($template_dirs as $template_dir) {
            if (is_file($template_dir . $template_file)) {
                $is_template_exist = true;
                break;
            }
        }
        return $is_template_exist;
    }
}
