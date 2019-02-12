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
 * information render adapter abstract class
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 */
namespace lightmvc\info\adapter;

use lightmvc\info\InfoCollector;

abstract class AbstractInfoRenderAdapter
{

    /**
     * sort message value list by timestamp(low -> high)
     * @param array $message_list
     */
    protected function sortMessageListByTimestamp($message_list)
    {
        if (empty($message_list)) {
            return [];
        }
        $timestamp_list = [];
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
