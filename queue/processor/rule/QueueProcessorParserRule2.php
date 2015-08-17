<?php
namespace queue\processor\rule;

use queue\processor\rule\QueueProcessorParserRule;

/**
 * Queue Processor Parser
 * =======================================================
 * parse server response data from server
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @package appLog\logic\queue\processor\rule
 * @version 1.0
 **/
class QueueProcessorParserRule2  extends QueueProcessorParserRule {

    /**
     * parse response into array with succeeded and error_code
     * @param array | string $response
     * @return boolean | array
     */
    public function parse($response) {

        if (empty($response)) {
            return false;
        }
        if (!is_array($response)) {
            $response = json_decode($response, true);
        }
        if (!isset($response[0]['succeeded'])) {
            return false;
        }
        return array(
            'succeeded' => $response[0]['succeeded'],
            'error_code' => $response[0]['error_code']
        );
    }
}