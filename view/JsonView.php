<?php
namespace view;

use exception\ExceptionCode;
use String;
use view\ViewInterface;

/**
 * Json View
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class JsonView implements ViewInterface {

    protected $_output_encode = String::ENCODE_UTF8;

    /**
     * initialize
     */
    public function init($config = null) {
        if (!empty($config['output_encode'])) {
            $this->_output_encode = $config['output_encode'];
        }
    }
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::getOutputEncode()
     */
    public function getOutputEncode() {
        return $this->_output_encode;
    }
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param) {

        if (!function_exists('json_encode')) {
            throw new ViewException('json not supported', ExceptionCode::VIEW_EXTENSION_NOT_LOAD);
        }
        if (empty($template_var)) {
            return '';
        }
        $output = json_encode($template_var);
        if ($this->_output_encode != String::ENCODE_UTF8) {
            $output = String::convert2UTF8($output, String::ENCODE_UTF8);
        }
        return $output;
    }
}
