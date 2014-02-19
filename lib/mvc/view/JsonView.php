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

    private $output_encode = 'UTF-8';

    /**
     * initialize
     */
    public function init($config = null) {}
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param) {

        if (!function_exists('json_encode')) {
            throw new ViewException(__message('json not supported'));
        }
        if (empty($template_var)) {
            return ;
        }
        send_http_response('json', $template_var);
    }
}
