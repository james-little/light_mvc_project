<?php
namespace view;

/**
 * Smarty View
 * =======================================================
 * smarty implementation of view interface
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
use context\RuntimeContext,
    exception\ExceptionCode,
    \Smarty,
    info\InfoCollector,
    exception\ViewException,
    view\ViewInterface;

require dirname(FRAMEWORK_ROOT_DIR) . DIRECTORY_SEPARATOR . 'Smarty/libs/Smarty.class.php';

class SmartyView extends Smarty implements ViewInterface {

    private $output_encode = 'UTF-8';

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
    public function init($config = null) {

        $allowed_settings = array(
            'template_dir', 'compile_dir', 'cache',
            'php_dir', 'plugins_dir', 'left_delimiter',
            'right_delimiter', 'output_encode'
        );
        $config = $config['settings'];
        if (empty($config)) {
            return ;
        }
        foreach ($config as $key => $value) {
            if (empty($value) || !in_array_pro($key, $allowed_settings)) {
                continue ;
            }
            switch ($key) {
                case 'cache':
                    $this->caching = empty($value['enable']) ? 0 : 2;
                    $this->cache_lifetime = empty($value['expire_time']) ? 3600 : $value['expire_time'];
                    $this->cache_dir = empty($value['dir']) ? '/tmp' : $value['dir'];
                    break;
                default:
                    $this->$key = $value;
                    break;
            }
            // message
            __add_info(__message('view initialized: %s', array($key)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
        $this->php_handling = Smarty::PHP_ALLOW;
    }
    /**
     * set output encode
     * @param string $output_encode
     */
    public function setOutputEncode($output_encode) {
        if (empty($output_encode)) {
            return ;
        }
        $this->output_encode = $output_encode;
    }
    /**
     * get output encode
     */
    public function getOutputEncode() {
        return $this->output_encode;
    }
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::assign()
     */
    public function assign_var($key, $value, $is_by_reference = true) {
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
    public function render($template_file, $template_var, $cache_param = null) {

        if (empty($template_file)) {
            return ;
        }
        $compile_id = null;
        if (!$this->caching && !empty($cache_param['compile_id'])) {
            $compile_id = $cache_param['compile_id'];
        }
        // assign variables to template when not use cache or cache is not exists
        if (!$this->isCached($template_file, $compile_id, $compile_id)) {
            if (empty($template_file) || !$this->templateExists($template_file)) {
                throw new ViewException(__message('template file not exist'));
            }
            // assign template var
            if (!empty($template_var)) {
                foreach ($template_var as $key => $value) {
                    $this->assign_var($key, $value);
                }
            }
        }
        $this->muteExpectedErrors();
        $output = $this->fetch($template_file, $compile_id, $compile_id);
        $this->unmuteExpectedErrors();
        $output = $this->convertOutputEncoding($output);
        if ($output) {
            header("Content-Type: text/html; charset={$this->output_encode}");
            echo $output;
        }
    }
    /**
     * convert output encoding
     * @param string $output
     * @return string
     */
    private function convertOutputEncoding($output) {
        if ($output && strtolower($this->output_encode) != 'utf-8') {
            $output = convert2utf8($output);
        }
        return $output;
    }
}
