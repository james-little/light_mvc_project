<?php
namespace view;

require dirname(FRAMEWORK_ROOT_DIR) . DIRECTORY_SEPARATOR . 'raintpl/inc/rain.tpl.class.php';

use info\InfoCollector,
    \RainTPL,
    view\ViewInterface,
    exception\ExceptionCode,
    exception\ViewException;

/**
 * Rain View
 * =======================================================
 * implementation of view interface using rain template engine
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

class RainView extends RainTPL implements ViewInterface {

    private $cache_expire_time;
    private $is_cache_enabled;
    protected $_output_encode = 'utf-8';

    /**
     * initialization
     *   tpl_dir           : template directory
     *   base_url          : base url
     *   cache_dir         : cache directory
     *   path_replace      : path replace
     *   path_replace_list : path replace list
     *   tpl_ext           : template extension
     */
    public function init($config = null) {

        $allowed_settings = array(
            'tpl_dir', 'cache', 'base_url', 'output_encode',
            'tpl_ext', 'path_replace', 'black_list'
        );
        if (empty($config)) {
            return ;
        }
        foreach ($config as $key => $value) {
            if (empty($value) || !in_array_pro($key, $allowed_settings)) {
                continue ;
            }
            switch ($key) {
                case 'path_replace':
                    self::configure($key, $value['enabled']);
                    if($value['enabled']) {
                        self::configure('path_replace_list', $value['list']);
                    }
                    break;
                case 'tpl_ext':
                    self::configure('tpl_ext', empty($value) ? 'tpl' : $value);
                    break;
                case 'cache':
                    $this->is_cache_enabled = empty($value['enabled']) ? false : true;
                    $this->cache_expire_time = empty($value['expire_time']) ? 3600 : $value['expire_time'];
                    self::configure('cache_dir', empty($value['dir']) ? 'tmp/' : $value['dir']);
                    break;
                case 'output_encode':
                    $this->_output_encode = strtolower($value);
                    break;
                default:
                    self::configure($key, $value);
                    break;
            }
            // message
            __add_info('view initialized: ' . $key, InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
        // enable php
        self::$php_enabled = true;
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
     * @see view.ViewInterface::assign()
     */
    public function assign_var($key, $value, $is_by_reference = true) {

        if ($is_by_reference) {
            parent::assignByRef($key, $value);
        } else {
            parent::assign($key, $value);
        }
    }
    /**
     * clear cache
     */
    public function clearCache() {
        array_map("unlink", glob(self::$cache_dir . '*.rtpl.php'));
    }
    /**
     * (non-PHPdoc)
     * @see view.ViewInterface::render()
     */
    public function render($template_file, $template_var, $cache_param = null) {

        $template_file = preg_replace('#\.' . self::$tpl_ext . '$#', '', $template_file);
        if ($this->is_cache_enabled) {
            $output = $this->cache($template_file, $this->cache_expire_time);
        } else {
            if (empty($template_file)) {
                throw new ViewException('template file not exist', ExceptionCode::VIEW_TEMPLATE_NOT_FOUND);
            }
            // assign template var
            if (!empty($template_var)) {
                foreach ($template_var as $key => $value) {
                    $this->assign_var($key, $value);
                }
            }
            $output = $this->draw($template_file, true);
        }
        if ($this->_output_encode != 'utf-8') {
            $output = mb_convert_encoding($output, $this->_output_encode, 'utf-8');
        }
        return $output;
//         send_http_response('html', $output);
    }
}
