<?php

/**
 * QueueException
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace exception;

use exception\ExceptionCode,
    exception\AppException;

class QueueException extends AppException {

    /**
     * __constructor
     * @param string $message
     * @param int $code
     * @param Exception
     */
    public function __construct($message = '', $code = ExceptionCode::QUEUE_DEFAULT_ERROR, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}