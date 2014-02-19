<?php
namespace view;

require dirname(FRAMEWORK_ROOT_DIR) . DIRECTORY_SEPARATOR . 'raintpl/inc/rain.tpl.class.php';

use info\InfoCollector,
    \RainTPL,
    view\ViewInterface,
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

    private $output_encode = 'UTF-8';
    private $cache_expire_time;
    private $is_cache_enabled;

    /**
     * 初期化処理。
     * @param string $carrier 描画用キャリア名<br/>
     * 設定ファイルに設定されている<br/>
     * @param array $setting 設定用連想配列<br/>
     * 描画用キャリア名 :
     *   template_dir    : テンプレートディレクトリ(Smarty用)
     *   cache_dir       : キャッシュディレクトリ(Smarty用)
     *   php_dir         : PHPディレクトリ
     *   config_dir      : コンフィグディレクトリ(Smarty用)
     *   plugins_dir     : プラグインディレクトリ(Smarty用)
     *   other_dir       : それ以外のディレクトリ
     *   left_delimiter  : 左側デリミタ(Smarty用)
     *   right_delimiter : 右側デリミタ(Smarty用)
     */
    public function init($config = null) {

        $allowed_settings = array(
            'tpl_dir', 'cache', 'base_url',
            'tpl_ext', 'path_replace', 'black_list'
        );
        if (empty($config)) {
            return ;
        }
        $config = $config['settings'];
        foreach ($config as $key => $value) {
            if (empty($value) || !in_array_pro($key, $allowed_settings)) {
                continue ;
            }
            switch ($key) {
                case 'path_replace':
                    self::configure($key, $value['enabled']);
                    self::configure('path_replace_list', $value['list']);
                    break;
                case 'tpl_ext':
                    self::configure('tpl_ext', $value ? $value : 'tpl');
                    break;
                case 'cache':
                    $this->is_cache_enabled = empty($value['enabled']) ? false : true;
                    $this->cache_expire_time = empty($value['expire_time']) ? 3600 : $value['expire_time'];
                    self::configure('cache_dir', empty($value['dir']) ? 'tmp/' : $value['dir']);
                    break;
                default:
                    self::configure($key, $value);
                    break;
            }
            // message
            __add_info(__message('view initialized: %s', array($key)), InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
        }
        // enable php
        self::$php_enabled = true;
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
                throw new ViewException(__message('template file not exist'));
            }
            // assign template var
            if (!empty($template_var)) {
                foreach ($template_var as $key => $value) {
                    $this->assign_var($key, $value);
                }
            }
            $output = $this->draw($template_file, true);
        }
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
