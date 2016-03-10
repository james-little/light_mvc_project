<?php
namespace core;

use context\RuntimeContext;
use core\Controller;
use Exception;
use exception\ExceptionErrorConverter;
use view\ViewInterface;

/**
 * ErrorController
 * =======================================================
 * Any errorController used in this project should directly or indirectly
 * inherit ErrorController and overwrite _handle($e) method
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
abstract class ErrorController extends Controller {

    // exception error converter
    protected $_error_convertor;
    // default error template
    protected $_view_file_path = 'error/error.tpl';
    protected $_with_trace     = false;

    /**
     * get error exception
     */
    protected function getException() {

        if (isset($this->_controller_render_params['render_error_exception'])) {
            return $this->_controller_render_params['render_error_exception'];
        }
        return RuntimeContext::getInstance()->getData('render_error_exception');
    }
    /**
     * handle error exception
     */
    final public function handle() {

        if (!$this->_error_convertor) {
            $this->_error_convertor = ExceptionErrorConverter::getInstance();
        }
        if ($this->isAjaxRequest()) {
            $this->_view_type = ViewInterface::VIEW_TYPE_JSON;
        }
        $exception = $this->getException();
        $this->_handle($exception);
        $this->setErrorViewTemplate();
    }

    /**
     * handle exception
     * @param Exception $e
     */
    protected function _handle(Exception $e) {

        $error_code = $this->_error_convertor->get($e->getCode());
        if ($error_code === null) {
            throw $e;
        }
        $message = $e->getMessage();
        if ($this->_with_trace) {
            $message .= $e->getTraceAsString();
        }
        $this->setViewDefaultParams([
            'error_code' => $error_code,
            'message'    => $message,
        ]);
    }

    /**
     * set view default params
     * @param array $data
     * @return void
     */
    protected function setViewDefaultParams($data) {

        if ($this->_view_type == ViewInterface::VIEW_TYPE_JSON) {
            $this->setViewParam('is_success', (int) ($data['error_code'] == 0));
        }
        $this->setViewParam('error_code', $data['error_code']);
        $this->setViewParam('message', $data['message']);
    }

    /**
     * set customized error view template
     */
    protected function setErrorViewTemplate() {}
}
