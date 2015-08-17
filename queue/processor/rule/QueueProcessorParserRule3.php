<?php
namespace queue\processor\rule;

use queue\processor\rule\QueueProcessorParserRule;

/**
 * Queue Processor Parser
 * =======================================================
 * parse server response data from server
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @version 1.1
 **/
class QueueProcessorParserRule3  extends QueueProcessorParserRule {

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
        if (!isset($response['error_code'])) {
            return false;
        }
        return array(
            'succeeded' => $response['error_code'] == 0 ? true : false,
            'error_code' => $response['error_code']
        );
    }
}