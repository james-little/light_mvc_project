<?php

namespace log\writer;

/**
 * log writer interface
 * ====================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package log\writer
 * @version 1.0
 **/
interface LogWriterInterface {

    /**
     * write log
     * @param string $message
     * @param int $level
     */
    public function write($message, $level);

    /**
     * close adapter
     */
    public function close();
}