<?php

/**
 * InfoCollectorException
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace exception;

use exception\AppException,
    exception\ExceptionCode;

class InfoCollectorException extends AppException {
    /**
     * __constructor
     * @param string $message
     * @param int $code
     * @param Exception
     */
    public function __construct($message = '', $code = ExceptionCode::INFO_COLLECTOR_DEFAULT_ERROR, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}