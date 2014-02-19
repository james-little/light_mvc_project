<?php // -*- coding: utf-8 -*-
/**
 * ライブラリ例外クラス
 * @package pokefw
 * @copyright Copyright (c) 2011-2012, Pokelabo Inc.
 * @filesource
 */

namespace exception;

use \Exception,
    exception\ExceptionCode;

/**
 * ライブラリ例外クラス
 * @package pokefw
 */
class LibraryException extends Exception {

    /**
     * 例外を作成する
     * @param string $message 例外メッセージ(開発者向け)
     * @param int $code 例外コード
     * @param Exception $previous 以前に使われた例外。例外の連結に使用
     */
    public function __construct($message = '', $code = ExceptionCode::DEFAULT_LIBRARY_ERROR, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
