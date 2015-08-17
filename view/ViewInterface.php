<?php
namespace view;

/**
 * ViewInterface
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
interface ViewInterface {

    const VIEW_TYPE_JSON = 'j';
    const VIEW_TYPE_SMARTY = 's';
    const VIEW_TYPE_RAIN = 'r';
    const VIEW_TYPE_TEXT = 't';

    /**
     * initialize
     * @param array | null $config
     */
    public function init($config = null);
    /**
     * get output encode
     */
    public function getOutputEncode();
    /**
     * render template
     * @param string $template_file
     * @param array $template_var
     * @param array $cache_param
     * @return string
     */
    public function render($template_file, $template_var, $cache_param);
}
