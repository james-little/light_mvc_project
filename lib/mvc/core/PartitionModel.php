<?php
namespace core;

use core\Model,
    info\InfoCollector;

/**
 * partition model (DB)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
abstract class PartitionModel extends Model {

    CONST PARTITION_TYPE_LIST = 1;
    CONST PARTITION_TYPE_RANGE = 2;

    // range data per partition when using range partition
    protected $range_data_per_partition = 400000;
    protected $partition_type;
    protected $backup_partition = 'pbak';
    protected $backup_partition_val = 0;

    /**
     * partition check
     * @return boolean
     */
    public function checkPartition($args = null) {

        $create_table_str = $this->getCreateTableStr();
        if (!$create_table_str) {
            __add_info(__message('check partition error: %s table not exist', array($this->table)),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return false;
        }
        if (!$this->checkIsPartitionSupported($create_table_str)) {
            if (!$this->convertPartitionSupported()) {
                __add_info(__message('check partition error: %s table do not support partition' , array($this->table)),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
                return false;
            }
            $create_table_str = $this->getCreateTableStr();
        }
        if (!$this->checkBackupPartition($create_table_str)) {
            if (!$this->addBackupPartition()) {
                __add_info(__message('add backup partition failed: %s' , array($this->table)),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
                return false;
            }
        }
        return $this->_checkPartition($create_table_str, $args);
    }
    /**
     * get create table string
     */
    private function getCreateTableStr() {

        $sql = "SHOW CREATE TABLE `{$this->table}`";
        $result = $this->queryRow($sql);
        return empty($result) ? false : $result['Create Table'];
    }

    /**
     * check if table supported partition
     * @param string $create_table_str
     * @return bool
     */
    protected function checkIsPartitionSupported($create_table_str) {
        return (bool) preg_match('#PARTITION BY#', $create_table_str);
    }
    /**
     * check backup partition
     * @param string $create_table_str
     */
    protected function checkBackupPartition($create_table_str) {

        $pattern = "#PARTITION {$this->backup_partition}[^\n]+VALUES[^\n]+ENGINE = [^,]+#";
        if (!preg_match($pattern, $create_table_str)) {
            __add_info(__message('check backup partition error: pattern not match. table: %s # %s' , array($pattern, $create_table_str)),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return false;
        }
        return true;
    }
    /**
     * check backup partition
     * @param string $create_table_str
     */
    protected function addBackupPartition() {

        if (empty($this->partition_type)
            || !in_array($this->partition_type, array(self::PARTITION_TYPE_LIST, self::PARTITION_TYPE_RANGE))) {
            // partition type not support
            __add_info(__message('add backup partition failed: type not supported %s, %s' , array($this->table, $this->partition_type)),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
            return false;
        }
        $sql = null;
        switch ($this->partition_type) {
            case self::PARTITION_TYPE_LIST:
                $sql = "ALTER TABLE `{$this->table}`
                        ADD PARTITION (PARTITION {$this->backup_partition} VALUES IN ({$this->backup_partition_val}))";
                break;
            case self::PARTITION_TYPE_RANGE:
                $sql = "ALTER TABLE `{$this->table}`
                        ADD PARTITION (PARTITION {$this->backup_partition} VALUES LESS THAN (MAXVALUE))";
                break;
        }
        if (!$sql) {
            return false;
        }
        // retry for 3 times
        $is_success = false;
        $count = 3;
        while($count) {
            $is_success = $this->exec($sql);
            if ($is_success) {
                break;
            }
            $count --;
        }
        __add_info(__message(
            'add backup partition %s: %s' , array($is_success ? 'success' : 'failed', $this->table)),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
        return $is_success;
    }
    /**
     * add partition
     * @param array | null $args
     * @return boolean
     */
    public function addPartition($args, &$info = null) {


        // check if next partition already exists
        if ($this->checkPartition($args)) {
            // for some reason next partition already exist
            if ($info !== null) {
                $info['message'] = "partition already exists:{$this->table}";
            }
            return false;
        }
        // get next partition name
        $next_partition_name = $this->getNextPartitionName($args);
        if (!is_string($next_partition_name)) {
            // if next partition name is not string, means create partition name failed
            if ($info !== null) {
                $info['message'] = "get next partition name failed:{$this->table}#" . var_export($next_partition_name, true);
            }
            return false;
        }
        // get next partition value
        $next_partition_value = $this->getNextPartitionValue($args);
        if (!is_numeric($next_partition_value)) {
            // if next partition value is not numeric, means create partition value failed
            if ($info !== null) {
                $info['message'] = "get next partition value failed:{$this->table}#" . var_export($next_partition_value, true);
            }
            return false;
        }
        __add_info(__message(
            'add partition %s to %s: %s' , array($next_partition_name, $this->table, $next_partition_value)),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
        // make sql
        $sql = null;
        switch ($this->partition_type) {
            case self::PARTITION_TYPE_LIST:
                $sql = "
                    ALTER TABLE `{$this->table}` REORGANIZE PARTITION {$this->backup_partition} INTO (
                        PARTITION {$next_partition_name} VALUES IN ({$next_partition_value}),
                        PARTITION {$this->backup_partition} VALUES IN ({$this->backup_partition_val})
                )";
                break;
            case self::PARTITION_TYPE_RANGE:
                $sql = "
                    ALTER TABLE `{$this->table}` REORGANIZE PARTITION {$this->backup_partition} INTO (
                        PARTITION {$next_partition_name} VALUES LESS THAN ({$next_partition_value}),
                        PARTITION {$this->backup_partition} VALUES LESS THAN (MAXVALUE)
                )";
                break;
        }
        if (!$sql) {
            if ($info !== null) {
                $info['message'] = "partition type not supported:{$this->table}#" . $this->partition_type;
            }
            return false;
        }
        // retry for 3 times
        $is_success = false;
        $count = 3;
        while($count) {
            $is_success = $this->exec($sql);
            if ($is_success) {
                break;
            }
            $count --;
        }
        if ($info !== null) {
            $info['message'] = 'add parition ' . $is_success ? "success:{$this->table}#{$next_partition_name}" : "failed:{$this->table}";
        }
        return $is_success;
    }

    /**
     * check partition to know if it's need to add
     * @param string $create_table_str
     * @return bool
     */
    abstract protected function _checkPartition($create_table_str, $args = null);
    /**
     * get range value for next partition
     * @param array | null $args
     * @return mixed
     */
    abstract protected function convertPartitionSupported();
    /**
     * get next partition name
     * @param array | null $args
     * @return string
     */
    abstract protected function getNextPartitionName($args = null);
    /**
     * get range value for next partition
     * @param array | null $args
     * @return mixed
     */
    abstract protected function getNextPartitionValue($args = null);

}