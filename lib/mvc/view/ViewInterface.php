<?php
namespace view;

/**
 * ViewInterface
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
interface ViewInterface {

    /**
     * initialize
     * @param array | null $config
     */
    public function init($config = null);
    /**
     * render template
     * @param string $template_file
     * @param mixed $template_var
     * @param bool $is_return
     */
    public function render($template_file, $template_var, $cache_param);
}
