<?php
namespace view;

use exception\ExceptionCode,
    view\ViewInterface;

/**
 * Json View
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class JsonView implements ViewInterface {

    protected $_output_encode = 'utf-8';

    /**
     * initialize
     */
    public function init($config = null) {
        if (!empty($config['output_encode'])) {
            $this->_output_encode = strtolower($config['output_encode']);
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
        if ($this->_output_encode != 'utf-8') {
            $output = mb_convert_encoding($output, $this->_output_encode, 'UTF-8');
        }
        return $output;
//         send_http_response('json', $template_var);
    }
}
