<?php
namespace socket\processor\helper;

/**
 * Socket Data Processor Helper
 * =======================================================
 * process data commander
 *
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @package logic\adReward\socket\processor\helper
 * @version 1.1
 **/
class SocketProcessorHelperCommander extends SocketProcessorHelper {

    private $supported_runtime_command = array(
        'is_debug' => 0,
        'is_output_console' => 0,
        'killmyself' => 0
    );

    /**
     * filter data and do process
     * @param array | string $data
     * @param array | string $params
     */
    public function process($data, $params) {

        if (!isset($params['socket_server']) || !isset($params['socket'])) {
            return ;
        }
        if (!$this->judgeIsCommandData($data)) {
            return ;
        }
        $socket_server = $params['socket_server'];
        $execute_result = $this->execCommand($this->parseCommandData($data), $socket_server);
        $socket_server->log('excute_result: ' . var_export($execute_result, true));
        if ($execute_result === true || $execute_result == 'exit') {
            $socket_server->send($params['socket'], 'ok');
            if ($execute_result === 'exit') {
                $socket_server->stop();
                return ;
            }
        }
    }
    /**
     * get supported command list
     */
    public function getSupportedCommand() {
        return $this->supported_runtime_command;
    }
    /**
     * judge if is command line
     * @param mixed $data
     * @return bool
     * @return boolean
     */
    private function judgeIsCommandData($data) {
        return preg_match('#^socket_server://#', $data) ? true : false;
    }
    /**
     * command data is a string like:
     *     socket_server://is_output_console?value=true
     * @param string $data
     */
    private function parseCommandData($data) {

        if (!is_string($data)) {
            return false;
        }
        $data = preg_replace('#^socket_server://#', '', strtolower($data));
        $temp = array();
        if (!preg_match('#^([^\?]+)?#', $data, $temp)) {
            return false;
        }
        $command = $temp[1];
        $data = preg_replace('#^[^\?]+?#', '', $data);
        $temp = array();
        $matches = preg_match_all('#([a-z_]+)=([0-9a-z_]+)#', $data, $temp, PREG_SET_ORDER);
        $params = array();
        foreach ($temp as $tmp_value_list) {
            $params[$tmp_value_list[1]] = $tmp_value_list[2];
        }
        unset($temp);
        unset($tmp_value_list);
        return array(
            'command' => $command,
            'params' => $params
        );
    }
    /**
     * excute command with command parameter
     * @param mixed $command_data
     * @return bool
     */
    private function execCommand($command_data, $socket_server) {

        if (!isset($command_data['command'])) {
            // check command data is correctly parsed
            return false;
        }
        if (!isset($this->supported_runtime_command[$command_data['command']])) {
            // check command is supported
            return false;
        }
        if (!isset($command_data['params'])) {
            $command_data['params'] = array();
        }
        return $this->exec($command_data['command'], $command_data['params'], $socket_server);
    }
    /**
     * execute command
     * @param string $command
     * @param mixed $value
     * @return bool
     */
    private function exec($command, $params, $socket_server) {

        $socket_server->log('start to execute command:' . $command . '#params:' . var_export($params, true));
        switch ($command) {
            case 'is_output_console':
                $value = empty($params['value']) ? false : (bool) $params['value'];
                $socket_server->setQueueProcessorStatus('is_output_console', $value);
                if ((bool) $value !== $socket_server->getQueueProcessorStatus('is_output_console')) {
                    $socket_server->log('ad_reward_queue_process output console set to ' . var_export($value, true));
                    $socket_server->console('ad_reward_queue_process output console set to ' . var_export($value, true));
                }
                break;
            case 'is_debug':
                $value = empty($params['value']) ? false : (bool) $params['value'];
                $socket_server->setQueueProcessorStatus('is_debug', $value);
                if ($value !== $socket_server->getQueueProcessorStatus('is_debug')) {
                    $socket_server->log('ad_reward_queue_process debug mode set to ' . var_export($value, true));
                    $socket_server->console('ad_reward_queue_process debug mode set to ' . var_export($value, true));
                }
                break;
            case 'killmyself':
                return 'exit';
            default:
                return false;
        }
        return true;
    }

}