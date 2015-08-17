<?php

namespace info;

use info\InfoCollector,
    info\adapter\AbstractInfoRenderAdapter;

/**
 * information render
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class InfoRender {

    private $info_collector;
    private $render_adapter_list;

    /**
     * __construct
     */
    public function __construct(InfoCollector $info_collector) {
        $this->info_collector = $info_collector;
    }
    /**
     * add adapter to render list
     * @param AbstractInfoRenderAdapter $adapter
     */
    public function addAdapter(AbstractInfoRenderAdapter $adapter) {
        $this->render_adapter_list[$this->getAdapterKey($adapter)] = $adapter;
    }
    /**
     * has adapter
     * @param AbstractInfoRenderAdapter $adapter
     * @return bool
     */
    public function hasAdapter(AbstractInfoRenderAdapter $adapter) {
    	return isset($this->render_adapter_list[$this->getAdapterKey($adapter)]);
    }
    /**
     * get adapter key
     * @param AbstractInfoRenderAdapter $adapter
     * @return string
     */
    private function getAdapterKey(AbstractInfoRenderAdapter $adapter) {
    	return get_class($adapter);
    }
    /**
     * render
     */
    public function render() {
		
    	if (empty($this->info_collector)) {
    		return ;
    	}
        if (empty($this->render_adapter_list)) {
            return ;
        }
        foreach ($this->render_adapter_list as $render_adapter) {
            $render_adapter->render($this->info_collector);
        }
    }
}
