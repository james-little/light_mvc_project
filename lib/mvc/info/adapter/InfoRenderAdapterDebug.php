<?php

namespace info\adapter;

use \Debugger,
    info\adapter\AbstractInfoRenderAdapter,
    info\InfoCollector;

/**
 * RenderAdapterDebugger
 * =======================================================
 * use Debugger to render
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package info\adapter
 * @version 1.0
 */
class InfoRenderAdapterDebug extends AbstractInfoRenderAdapter {

    /**
     * render
     */
    public function render(InfoCollector $info_collector) {
        // logic info
        $logic_message_list = $info_collector->getMessages(InfoCollector::TYPE_LOGIC);
        if (!empty($logic_message_list)) {
            foreach ($logic_message_list as $message_value) {
                if ($message_value['level'] > InfoCollector::LEVEL_DEBUG) {
                    continue ;
                }
                Debugger::getInstance()->debug($message_value['message']);
            }
        }
        unset($logic_message_list);
        // sql info
        $sql_message_list = $this->sortMessageListByTimestamp(
            $info_collector->getMessages(InfoCollector::TYPE_SQL)
        );
        if (!empty($sql_message_list)) {
            foreach ($sql_message_list as $message_value) {
                if ($message_value['level'] > InfoCollector::LEVEL_DEBUG) {
                    continue ;
                }
                Debugger::getInstance()->debugSql($message_value['message']);
            }
        }
        unset($sql_message_list);
    }

}
