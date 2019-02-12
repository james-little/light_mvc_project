<?php
/**
 * Copyright 2016 Koketsu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * RenderAdapterDebugger
 * =======================================================
 * use Debugger to render
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package info\adapter
 * @version 1.0
 */
namespace lightmvc\info\adapter;

use lightmvc\BatchDebugger;
use lightmvc\context\RuntimeContext;
use lightmvc\Debugger;
use lightmvc\info\adapter\AbstractInfoRenderAdapter;
use lightmvc\info\InfoCollector;

class InfoRenderAdapterDebug extends AbstractInfoRenderAdapter
{
    /**
     * render
     */
    public function render(InfoCollector $info_collector)
    {
        // logic info
        $logic_message_list = $info_collector->getMessages(InfoCollector::TYPE_LOGIC);
        if (!empty($logic_message_list)) {
            $debugger = RuntimeContext::getInstance()->getAppRunmode() == RuntimeContext::MODE_CLI ?
            BatchDebugger::getInstance() : Debugger::getInstance();
            foreach ($logic_message_list as $message_value) {
                if ($message_value['level'] > InfoCollector::LEVEL_DEBUG) {
                    continue;
                }
                $debugger->debug($message_value['message'], $message_value['level']);
            }
        }
    }
}
