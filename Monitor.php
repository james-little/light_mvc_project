<?php

/**
 * monitor class
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class Monitor {

    private static $start;

    /**
     * reset timer
     */
    static public function reset() {
        self::$start = microtime(true);
    }

    /**
     * stop timer
     */
    static public function stop() {
        return microtime(true) - self::$start;
    }
    /**
     * get peak memory usage
     */
    static public function getMemoryPeak() {
        return memory_get_usage(true) / 1024;
    }

}