<?php
namespace socket\processor\helper;

/**
 * Socket Data Processor Helper
 * =======================================================
 * help socket processor to process different kinds of data
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @package logic\adReward\socket\processor\helper
 * @version 1.1
 **/
abstract class SocketProcessorHelper  {



    /**
     * filter data and do process
     * @param array | string $data
     * @param array | string $params
     */
    abstract public function process($data, $params);

}