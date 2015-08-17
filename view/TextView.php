<?php
namespace view;

use view\ViewInterface;

/**
 * Text
 */
class TextView implements ViewInterface {

    private $filter_var_function;
    protected $_output_encode = 'utf-8';

    /**
     * initialize
     */
    public function init($config = null) {
        // filter var function
        if (!empty($config['filter_var_function']))
            $this->filter_var_function = $config['filter_var_function'];
        // output code
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
     * render
     * (non-PHPdoc)
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param) {
        return $this->getTemplateVarText($template_var);
//         send_http_response('text', $this->getTemplateVarText($template_var));
    }

    private function toTxt($template_var) {
        if (!is_array($template_var)) {
            return "invalid paramter";
        }
        $txt = "";
        foreach($template_var as $key => $val) {
            $txt .= "{$key}:{$val};";
        }
        return substr($txt, 0, -1);
    }
    /**
     * get filtered template var text
     * @param array|string $template_var
     * @return string
     */
    public function getTemplateVarText($template_var) {
        if (is_string($template_var)) return $template_var;
        if (!$this->filter_var_function || !function_exists($this->filter_var_function)) {
            return $this->toTxt($template_var);
        }
        $output = call_user_func($this->filter_var_function, $template_var);
        if ($this->_output_encode == 'utf-8') {
            return $output;
        }
        return mb_convert_encoding($output, $this->_output_encode, 'utf-8');
    }
}
