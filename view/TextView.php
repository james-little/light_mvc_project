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
 * Text
 */
namespace lightmvc\view;

use lightmvc\Encoding;
use lightmvc\view\ViewInterface;

class TextView implements ViewInterface
{

    private $filter_var_function;
    protected $_output_encode = Encoding::ENCODE_UTF8;

    /**
     * initialize
     */
    public function init($config = null)
    {
        // filter var function
        if (!empty($config['filter_var_function'])) {
            $this->filter_var_function = $config['filter_var_function'];
        }
        // output code
        if (!empty($config['output_encode'])) {
            $this->_output_encode = strtolower($config['output_encode']);
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
     * render
     * (non-PHPdoc)
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param)
    {
        return $this->getTemplateVarText($template_var);
    }

    private function toTxt($template_var)
    {
        if (!is_array($template_var)) {
            return "invalid paramter";
        }
        $txt = "";
        foreach ($template_var as $key => $val) {
            $txt .= "{$key}:{$val};";
        }
        return substr($txt, 0, -1);
    }
    /**
     * get filtered template var text
     * @param array|string $template_var
     * @return string
     */
    public function getTemplateVarText($template_var)
    {
        if (is_string($template_var)) {
            return $template_var;
        }

        if (!$this->filter_var_function || !function_exists($this->filter_var_function)) {
            return $this->toTxt($template_var);
        }
        $output = call_user_func($this->filter_var_function, $template_var);
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
