<?php
namespace queue;

use exception\ExceptionCode,
    exception\QueueProcessorException,
    \Communicator,
    \Monitor,
    queue\Queue,
    queue\processor\QueueProcessorParser,
    log\writer\LogWriterStream,
    appLog\model\AppLogDataModel,
    \Url;

/**
 * Queue Processor
 * =======================================================
 * Get queue data from the queue container and send request(s) to a
 * specified server.
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class QueueProcessor  {

    protected $_queue;
    protected $_communicator;
    protected $_is_debug = false;
    protected $_is_output_console = false;
    protected $_log;
    protected $_log_file;

    /**
     * __construct
     */
    public function __construct($config = null) {
        if (is_array($config) && count($config)) {
            $this->applyConfig($config);
        }
    }
    /**
     * apply config to class
     * @param array $config
     * @throws QueueProcessorException
     */
    public function applyConfig($config) {

        if (!isset($config['communicator'])) {
            throw new QueueProcessorException(
                "no communicator config", ExceptionCode::QUEUEPROCESSOR_CONFIG_ERROR);
        }
        if (!isset($config['queue'])) {
            throw new QueueProcessorException(
                "no queue config", ExceptionCode::QUEUEPROCESSOR_CONFIG_ERROR);
        }
        // queue
        if (!$this->_queue) {
            $this->_queue = Queue::getInstance();
            $this->_queue->applyConfig($config['queue']);
        }
        // communicator
        $communicator_config = $config['communicator'];
        $this->_communicator = new Communicator($communicator_config);
        if(isset($communicator_config['is_multi_on'])) {
            $this->_communicator->setIsMultiOn($communicator_config['is_multi_on']);
        }
        // debug mode
        if(isset($config['is_debug'])) {
            $this->_is_debug = (bool) $config['is_debug'];
        }
        // output console
        if(isset($config['is_output_console'])) {
            $this->_is_output_console = (bool) $config['is_output_console'];
        }
        // debug log file
        if(isset($config['log_file'])) {
            $this->_log_file = $config['log_file'];
        }
    }
    /**
     * @param Communicator $communicator
     */
    public function setCommunicator(Communicator $communicator) {
        $this->_communicator = $communicator;
    }
    /**
     * @return Ambigous <communicator\Communicator, Communicator>
     */
    public function getCommunicator() {
        return $this->_communicator;
    }
    /**
     * set is output to console
     */
    public function setIsOutputConsole($is_output_console) {
        $this->_is_output_console = $is_output_console;
    }
    /**
     * get is debug mode
     * @return bool
     */
    public function getIsOutputConsole() {
        return $this->_is_output_console;
    }
    /**
     * set is debug mode
     * @param bool $is_debug
     */
    public function setIsDebug($is_debug = false) {
        $this->_is_debug = (bool) $is_debug;
    }
    /**
     * get is debug mode
     * @return bool
     */
    public function getIsDebug() {
        return $this->_is_debug;
    }
    /**
     * set is debug mode
     * @param bool $is_debug
     */
    public function setLogFile($log_file) {
        $this->_log_file = $log_file;
    }
    /**
     * get is debug mode
     * @return bool
     */
    public function getLogFile() {
        return $this->_log_file;
    }
    /**
     * send all requests belongs to the specified user
     * @param $user_id
     */
    public function sendRequestByUserId($user_id) {

        $this->log('queue_processor#sendRequestByUserId: ' . $user_id);
        if (!$this->_communicator) {
            throw new QueueProcessorException('communicator was not set', ExceptionCode::QUEUEPROCESSOR_COMMUNICATOR_ERROR);
        }
        if (!$this->_queue) {
            throw new QueueProcessorException('queue was not set', ExceptionCode::QUEUEPROCESSOR_QUEUE_ERROR);
        }
        $count = 1;
        while (true) {
            Monitor::reset();
            $request = $this->_queue->getQueueByUserId($user_id);
            $spend_time = Monitor::stop();
            $this->log("queue_processor#sendRequestByUserId[{$count}][{$spend_time}] :" . var_export($request, true));
            if (empty($request)) {
                $this->log('queue_processor#sendRequestByUserId: not more data exist');
                break;
            }
            $this->_sendRequest(array($request));
            $count ++;
        }
    }
    /**
     * send all specified request(s)
     * @param $request_id_list
     */
    public function sendRequestByRequestIdList($queue_id_list) {

        if (empty($queue_id_list)) return ;
        if (!$this->_communicator) {
            throw new QueueProcessorException('communicator was not set', ExceptionCode::QUEUEPROCESSOR_COMMUNICATOR_ERROR);
        }
        if (!$this->_queue) {
            throw new QueueProcessorException('queue was not set', ExceptionCode::QUEUEPROCESSOR_QUEUE_ERROR);
        }
        $this->log('queue_processor#sendRequestByRequestIdList: ' . var_export($queue_id_list, true));
        foreach ($queue_id_list as $queue_id) {
            Monitor::reset();
            $request = $this->_queue->getByQueueId($queue_id);
            $spend_time = Monitor::stop();
            $this->log("queue_processor#sendRequestByRequestIdList[{$spend_time}] :" . var_export($request, true));
            $this->_sendRequest(array($request));
        }
        $this->log('queue_processor#sendRequestByRequestIdList: end');
    }
    /**
     * send all request(s) by processes
     * @param $process_id
     */
    public function sendRequest($process_num = 0, $process_id = 0) {

        if (!$this->_communicator) {
            throw new QueueProcessorException('communicator was not set', ExceptionCode::QUEUEPROCESSOR_COMMUNICATOR_ERROR);
        }
        if (!$this->_queue) {
            throw new QueueProcessorException('queue was not set', ExceptionCode::QUEUEPROCESSOR_QUEUE_ERROR);
        }
        if ($process_num <= 1) {
            $process_num = 0;
        }
        $this->log("queue_processor#sendRequest: process_num#{$process_num}#process_id#{$process_id}");
        $count = 1;
        while (true) {
            Monitor::reset();
            $request = $this->_queue->get($process_num, $process_id);
            $spend_time = Monitor::stop();
            $this->log("queue_processor#sendRequest[{$count}][{$spend_time}] :" . var_export($request, true));
            if (empty($request)) {
                $this->log('queue_processor#sendRequest: not more data exist');
                break;
            }
            $this->_sendRequest(array($request));
            $count ++;
        }
    }
    /**
     * send reqest under single user mode
     * @param array $request_list
     */
    protected function _sendRequest($request_list) {

        if (empty($request_list)) {
            return ;
        }
        $resource_id_mapping = array();
        $request_list_count = count($request_list);

        while($request_list_count) {

            $key = key($request_list);
            $request = current($request_list);
            if (empty($request['request_url'])) {
                unset($request_list[$key]);
                $request_list_count --;
                $this->_queue->delete($request['id']);
                $this->_queue->resetLock($request['id']);
                continue;
            }
            $request_id = $request['id'];
            $url = $request['request_url'];

            $is_by_post = false;
            $post_var = null;
            // if the request is send by post then decode the request params,
            // add the request params to the url if not
            if ($request['http_method'] == AppLogDataModel::REQUEST_TYPE_POST) {
                $is_by_post = true;
                $post_var = empty($request['request_params']) ? array() : json_decode($request['request_params'], true);
            }elseif ($request['http_method'] == AppLogDataModel::REQUEST_TYPE_GET) {
                $request_param_list = empty($request['request_params']) ? array() : json_decode($request['request_params'], true);
                $url = Url::addGetParamsToUrl($request['request_url'], $request_param_list);
            }
            $resource_id = $this->_communicator->addUrl($url, $is_by_post, $post_var);
            if(is_numeric($resource_id)) {
                $resource_id_mapping[$resource_id] = $request['id'];
                unset($request_list[$key]);
                $request_list_count--;
            }
            if(!is_numeric($resource_id) || !$request_list_count) {
                $content_list = $this->_communicator->send(true);
                $this->updateRequestStatus($content_list, $resource_id_mapping);
                $resource_id_mapping = array();
                unset($content_list);
                $content_list = null;
            }
        }
    }

    /**
     * update request status
     * @param array $contents_list
     * @param array $resource_id_mapping
     */
    protected function updateRequestStatus($contents_list, $resource_id_mapping = null) {

        $success_request_id_list = array();
        // create response parser
        $queue_processor_parser = \ClassLoader::loadClass(
            '\appLog\logic\queue\processor\QueueProcessorParser');
        $queue_processor_parser->addRule(
            '\appLog\logic\queue\processor\rule\QueueProcessorParserRule1');
        $queue_processor_parser->addRule(
            '\appLog\logic\queue\processor\rule\QueueProcessorParserRule2');
        $queue_processor_parser->addRule(
            '\appLog\logic\queue\processor\rule\QueueProcessorParserRule3');
        if(!$this->_communicator->getIsMultiOn()) {
            $error_code = 0;
            $succeeded = NULL;
            $http_code = 0;
            $resource_id = key($resource_id_mapping);
            $request_id = current($resource_id_mapping);
            if ($contents_list['contents'] === NULL || $contents_list['contents'] === false) {
                $this->_queue->resetLock($request_id);
                $this->_queue->ignore($request_id);
            } else {
                $decoded_contents = json_decode($contents_list['contents'], true);
                // parse contents
                $parsed_result = $queue_processor_parser->parse($decoded_contents);
                if (is_array($parsed_result)) {
                    $succeeded = $parsed_result['succeeded'];
                    $error_code = $parsed_result['error_code'];
                }
                if (!$parsed_result) {
                    // write parse failed format to log
                    $this->log("queue_processor#updateRequestStatus:" . var_export($decoded_contents, true));
                }
                unset($decoded_contents);
                $decoded_contents = null;
            }
            if (is_bool($succeeded) && $succeeded) {
                $success_request_id_list[] = $request_id;
            } else {
                if(!empty($contents_list['info']['http_code'])) {
                    $http_code = $contents_list['info']['http_code'];
                }
                $this->_queue->updateErrorCode($request_id, $error_code, $http_code);
            }
        } else {
            foreach ($contents_list as $resource_id => $contents) {
                if(!$contents) {
                    continue;
                }
                $error_code = 0;
                $http_code = 0;
                $succeeded = NULL;
                $request_id = $resource_id_mapping[$resource_id];
                if ($contents['contents'] === NULL || $contents['contents'] === false) {
                    $this->_queue->resetLock($request_id);
                    $this->_queue->ignore($request_id);
                    continue;
                } else {
                    $decoded_contents = json_decode($contents['contents'], true);
                    $parsed_result = $queue_processor_parser->parse($decoded_contents);
                    if (is_array($parsed_result)) {
                        $succeeded = $parsed_result['succeeded'];
                        $error_code = $parsed_result['error_code'];
                    }
                    if (!$parsed_result) {
                        // write parse failed format to log
                        $this->log("queue_processor#updateRequestStatus:" . var_export($decoded_contents, true));
                    }
                    unset($decoded_contents);
                    $decoded_contents = null;
                }
                if (is_bool($succeeded) && $succeeded) {
                    $success_request_id_list[] = $request_id;
                    continue;
                } else {
                    if(!empty($contents['info']['http_code'])) {
                        $http_code = $contents['info']['http_code'];
                    }
                    $this->_queue->updateErrorCode($request_id, $error_code, $http_code);
                }
            }
        }
        $this->_queue->setRequestSuccess($success_request_id_list);
    }
    /*
     * log
     */
    protected function log($message) {

        if (empty($this->_is_debug) || empty($this->_log_file) || !is_file($this->_log_file)) {
            return ;
        }
        if ($message == '') {
            return ;
        }
        $message = '[' . date('Y/m/d H:i:s') . ']' . $message . "\n";
        if ($this->_is_output_console) {
            $this->logByConsole($message);
        } else {
            $this->logByFile($message);
        }
    }
    /**
     * output to console
     * @param string $message
     */
    private function logByConsole($message){
        echo $message;
    }

    private function logByFile($message) {
        if ($this->_log === null) {
            $this->_log = \ClassLoader::loadClass('\log\Log');
        }
        @file_put_contents($this->_log_file, '', FILE_APPEND);
        $this->_log->setWriter(new LogWriterStream($this->_log_file));
        $this->_log->log($message);
    }
}