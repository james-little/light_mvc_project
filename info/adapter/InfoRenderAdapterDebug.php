<?php

namespace info\adapter;

use context\RuntimeContext,
    exception\InfoCollectorException,
    \Debugger,
    \BatchDebugger,
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
            $debugger = RuntimeContext::getInstance()->getAppRunmode() == RuntimeContext::MODE_CLI ?
                BatchDebugger::getInstance() : Debugger::getInstance();
            foreach ($logic_message_list as $message_value) {
                if ($message_value['level'] > InfoCollector::LEVEL_DEBUG) {
                    continue ;
                }
                $debugger->debug($message_value['message'], $message_value['level']);
            }
        }
    }
}
