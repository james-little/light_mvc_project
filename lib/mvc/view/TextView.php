<?php
namespace view;

use view\ViewInterface;

/**
 * Text
 */
class TextView implements ViewInterface {

    private $output_encode = 'UTF-8';
    private $filter_var_function;

    /**
     * initialize
     */
    public function init($config = null) {
        if (!empty($config['filter_var_function']))
            $this->filter_var_function = $config['filter_var_function'];
    }
    /**
     * render
     * (non-PHPdoc)
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param) {
        send_http_response('text', $this->getTemplateVarText($template_var));
    }
    /**
     * get filtered template var text
     * @param array|string $template_var
     * @return string
     */
    public function getTemplateVarText($template_var) {
        if (is_string($template_var)) return $template_var;
        if (!function_exists($this->filter_var_function)) {
            return false;
        }
        return call_user_func($this->filter_var_function, $template_var);
    }
}
