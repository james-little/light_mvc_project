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
 * Queue Processor Parser
 * =======================================================
 * parse server response data from server
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\queue\processor\rule;

use queue\processor\rule\QueueProcessorParserRule;

class QueueProcessorParserRule3 extends QueueProcessorParserRule
{

    /**
     * parse response into array with succeeded and error_code
     * @param array | string $response
     * @return boolean | array
     */
    public function parse($response)
    {
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
            'succeeded'  => $response['error_code'] == 0 ? true : false,
            'error_code' => $response['error_code'],
        );
    }
}
