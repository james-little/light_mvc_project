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
 * QueueAdapter interface
 * =======================================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
namespace lightmvc\queue\adapter;

interface QueueAdapterInterface
{


    /**
     * add a request to queue
     * @param $data
     * @return bool
     */
    public function add($data);

    /**
     * set a queue data to logical delete status
     * @param int $queue_id
     * @return int
     */
    public function delete($queue_id);

    /**
     * get queue data by process number and process id.
     * set process(thread) number and process(thread) id if over 1 job worker
     * process is processing
     * @param int $process_num
     * @param int $process_id
     * @return array
     */
    public function get($process_num = 1, $process_id = 0);

    /**
     * get queue data by user id
     * @param int $user_id
     * @return array
     */
    public function getQueueDataByUserId($user_id);

    /**
     * get queue data by queue id
     * @param id $queue_id
     */
    public function getByQueueId($queue_id);

    /**
     * set a(some) queue data to success status by queue id
     * returns affected row count
     * @param array<int> $request_id_list
     * @return int
     */
    public function setQueueSuccess($queue_id_list);

    /**
     * confirm if the queue data is unprocessed
     * @param int $request_id
     * @return bool
     */
    public function confirmUnprocess($queue_id);
    /**
     * update queue data if request failed
     * @param int $request_id
     * @param int $error_code
     * @param int $http_code
     */
    public function updateErrorCode($queue_id, $error_code, $http_code);

    /**
     * jump the queue point over a specified queue data
     * @param int $request_id
     */
    public function ignore($queue_id);

    /**
     * release a queue data lock
     * @param int $request_id
     */
    public function resetLock($queue_id);
}
