<?php

namespace info\adapter;

use info\InfoCollector;

/**
 * information render adapter abstract class
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 */
abstract class AbstractInfoRenderAdapter {

    /**
     * sort message value list by timestamp(low -> high)
     * @param array $message_list
     */
    protected function sortMessageListByTimestamp($message_list) {

        if (empty($message_list)) {
            return array();
        }
        $timestamp_list = array();
        foreach ($message_list as $message_value) {
            $timestamp_list[] = $message_value['timestamp'];
        }
        array_multisort($timestamp_list, SORT_ASC, $message_list);
        unset($timestamp_list);
        return $message_list;
    }

    /**
     * render information collected
     * @param InfoCollector $info_collector
     * @return void
     */
    abstract public function render(InfoCollector $info_collector);
}
