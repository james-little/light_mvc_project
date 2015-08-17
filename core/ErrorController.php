<?php
namespace core;

use exception\ExceptionErrorConverter;
use core\Controller,
    view\ViewInterface,
    Exception;

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

    protected $_error_convertor;

    // default error template
    protected $_view_file_path = 'error/error.tpl';

    /**
     * get error exception
     */
    protected function getException() {
        return $this->_controller_render_params['render_error_exception'];
    }

    /**
     * handle error exception
     */
    final public function handle() {
        if(!$this->_error_convertor) {
            $this->_error_convertor = ExceptionErrorConverter::getInstance();
        }
        $this->_handle($this->getException());
        $this->setErrorViewTemplate();
    }

    /**
     * handle exception
     * @param \Exception $e
     */
    protected function _handle(Exception $e) {
        $error_code = $this->_error_convertor->get($e->getCode());
        if($error_code === null) {
            throw $e;
        }
        $this->setViewDefaultParams(array(
            'error_code' => $error_code,
            'message' => $e->getMessage()
        ));
    }

    /**
     * set view default params
     * @param array $data
     * @return void
     */
    protected function setViewDefaultParams($data) {
        if($this->_view_type == ViewInterface::VIEW_TYPE_JSON) {
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
