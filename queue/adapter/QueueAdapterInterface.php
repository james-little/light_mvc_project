<?php
namespace queue\adapter;
/**
 * QueueAdapter interface
 * =======================================================
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @version 1.1
 **/
interface QueueAdapterInterface {


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