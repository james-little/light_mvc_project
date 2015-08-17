<?php
namespace queue\processor\rule;

/**
 * Queue Processor Parser
 * =======================================================
 * parse server response data from server
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @version 1.1
 **/
abstract class QueueProcessorParserRule  {

    /**
     * parse response into array with succeeded and error_code
     * @param array | string $response response string in JSON format
     * @return boolean | array
     */
    abstract public function parse($response);
}