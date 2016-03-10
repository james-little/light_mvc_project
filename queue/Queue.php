<?php
namespace queue;

use exception\ExceptionCode,
    exception\QueueException,
    queue\adapter\QueueAdapterInterface;

/**
 * Queue(singletone).
 * =======================================================
 * Main queue logic.
 *
 * Function:
 *     . configure the queue by config
 *     . Set the number of queue data you can get from the queue per time
 *     . Get data from queue data container(id asc)
 *     . Get data from queue data container by user_id
 *     . Get data from queue data container by $queue_id_list
 *     . Set specified request(s) to succeed status
 *     . Set specified request(s) to logical deleted
 *     . Confirm if a specified request is unprocessded
 *
 * Example:
 *
 *     Normal:
 *     ##############################################################
 *     $queue = Queue::getInstance();
 *     $queue_adapter = new QueueAdapter();
 *     $queue->setAdapter($queue_adapter);
 *     $queue->get();
 *
 *     Use Config
 *     ###############################################################
 *     Config:
 *         . adapter         : adapter class name
 *
 *     $queue = Queue::getInstance();
 *     $config = array(
 *         'adapter' => 'QueueAdapter'
 *     );
 *     $queue->applyConfig($config);
 *     $queue->get();
 *
 * @version 1.0
 **/
class Queue {

    protected static $instance;
    protected $_adapter;

    /**
     * __construct
     * @param QueueAdapterInterface $adapter
     */
    protected function __construct(QueueAdapterInterface $adapter = null) {
        if ($adapter) $this->_adapter = $adapter;
    }

    /**
     * Configure queue by config
     * @param array $config
     *     . adapter         : string. adapter class name
     */
    public function applyConfig($config) {
        if (empty($config)) return ;
        if ($this->_adapter) return ;
        $adapter_name = $config['adapter'];
        if (!$adapter_name) {
            throw new QueueException('no adapter was set in queue', ExceptionCode::QUEUE_CONFIG_ERROR);
        }
        $this->_adapter = \ClassLoader::loadClass($adapter_name);
        $this->_adapter->applyConfig($config);
    }
    /**
     * adapter setter
     */
    public function setAdapter(QueueAdapterInterface $adapter) {
        $this->_adapter = $adapter;
    }
    /**
     * adapter getter
     * @return QueueAdapterInterface
     */
    public function getAdapter() {
        return $this->_adapter;
    }
    /**
     * singletone
     */
    static public function getInstance(QueueAdapterInterface $adapter = null) {
        if (self::$instance !== null) {
            return self::$instance;
        }
        self::$instance = new static($adapter);
        return self::$instance;
    }
    /**
     * Add a request data to queue
     * @param array $user_data
     *     . int     user_id
     * @param array $url_data
     *     . string url
     *     . array params
     * @param bool $is_by_post
     * @return int queue_id
     */
    public function add($user_data, $queue_data, $is_by_post = false) {

        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        $queue_data = array_merge($queue_data, $user_data);
        $queue_data['http_method'] = $is_by_post ? 2 : 1;
        return $this->_adapter->add($queue_data);
    }
    /**
     * Set a request / some requests to logical deleted
     * @param $queue_id array
     * @return bool
     */
    public function delete($queue_id) {
        if (!$queue_id) return;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->delete($queue_id);
    }
    /**
     * pop a request from queue
     * @param int $process_num. The number of processes you want to use
     * @param int $process_id. The process_id you are using now
     * @throws QueueException
     * @return array
     */
    public function get($process_num = 1, $process_id = 0) {
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->get($process_num, $process_id);
    }
    /**
     * Get requests from queue by user_id.
     * @param int $user_id
     * @throws QueueException
     * @return array
     */
    public function getQueueDataByUserId($user_id) {
        if (!$user_id) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->getQueueDataByUserId($user_id);
    }
    /**
     * Get requests from queue by request id list
     * @param array $request_id_list
     * @throws QueueException
     * @return array
     */
    public function getByQueueId($queue_id, $is_by_force = false) {
        if (!$queue_id) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->getByQueueId($queue_id, $is_by_force);
    }
    /**
     * Set specified requests' status to succeed.
     * @param array $queue_id_list
     * @throws QueueException
     * @return bool
     */
    public function setQueueSuccess($queue_id_list) {
        if (!count($queue_id_list)) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->setQueueSuccess($queue_id_list);
    }

    /**
     * Confirm if specified request is unprocessed
     * @param int $queue_id
     * @throws QueueException
     * @return bool true if unprocessed
     */
    public function confirmUnprocess($queue_id) {
        if (!$queue_id) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->confirmUnprocess($queue_id);
    }
    /**
     * Update specified request withe error code
     * @param int $queue_id
     * @param int $error_code
     * @throws QueueException
     * @return bool true if updated
     */
    public function updateErrorCode($queue_id, $error_code, $http_code) {
        if (!$queue_id) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->updateErrorCode($queue_id, $error_code, $http_code);
    }
    /**
     * Update queue data
     * @param int $queue_id
     * @param array $queue_data
     * @throws QueueException
     * @return bool true if updated
     */
    public function update($queue_id, $queue_data) {
        if (!$queue_id) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->update($queue_id, $queue_data);
    }
    /**
     * release request lock
     * @param int $queue_id
     * @throws Exception
     */
    public function resetLock($queue_id = null) {
        if ($queue_id && !is_numeric($queue_id)) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->resetLock($queue_id);
    }
    /**
     * point to next request data
     * @param int $queue_id
     * @throws Exception
     */
    public function ignore($queue_id) {
        if (!$queue_id) return false;
        if (!$this->_adapter) {
            throw new QueueException('adapter was not set', ExceptionCode::QUEUE_ADAPTER_NOT_SET);
        }
        return $this->_adapter->ignore($queue_id);
    }
}