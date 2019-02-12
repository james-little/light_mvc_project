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
 * information render
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\info;

use lightmvc\info\adapter\AbstractInfoRenderAdapter;
use lightmvc\info\InfoCollector;

class InfoRender
{

    private $info_collector;
    private $render_adapter_list;

    /**
     * __construct
     */
    public function __construct(InfoCollector $info_collector)
    {
        $this->info_collector = $info_collector;
    }
    /**
     * add adapter to render list
     * @param AbstractInfoRenderAdapter $adapter
     */
    public function addAdapter(AbstractInfoRenderAdapter $adapter)
    {
        $this->render_adapter_list[$this->getAdapterKey($adapter)] = $adapter;
    }
    /**
     * has adapter
     * @param AbstractInfoRenderAdapter $adapter
     * @return bool
     */
    public function hasAdapter(AbstractInfoRenderAdapter $adapter)
    {
        return isset($this->render_adapter_list[$this->getAdapterKey($adapter)]);
    }
    /**
     * get adapter key
     * @param AbstractInfoRenderAdapter $adapter
     * @return string
     */
    private function getAdapterKey(AbstractInfoRenderAdapter $adapter)
    {
        return get_class($adapter);
    }
    /**
     * render
     */
    public function render()
    {
        if (empty($this->info_collector)) {
            return;
        }
        if (empty($this->render_adapter_list)) {
            return;
        }
        foreach ($this->render_adapter_list as $render_adapter) {
            $render_adapter->render($this->info_collector);
        }
    }
}
