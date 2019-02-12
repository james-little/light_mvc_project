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
 * Json View
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\view;

use lightmvc\Encoding;
use lightmvc\exception\ExceptionCode;
use lightmvc\view\ViewInterface;

class JsonView implements ViewInterface
{

    protected $_output_encode = Encoding::ENCODE_UTF8;

    /**
     * initialize
     */
    public function init($config = null)
    {
        if (!empty($config['output_encode'])) {
            $this->_output_encode = $config['output_encode'];
        }
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
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param)
    {
        if (!function_exists('json_encode')) {
            throw new ViewException('json not supported', ExceptionCode::VIEW_EXTENSION_NOT_LOAD);
        }
        if (empty($template_var)) {
            return '';
        }
        $output = json_encode($template_var);
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
