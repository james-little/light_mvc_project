<?php
namespace core;

use exception\ModelException;
use resource\cache\Cache;
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
    protected $partition_column = 'partition_flag';
    protected $backup_partition = 'pbak';
    protected $backup_partition_val = 0;
    protected $cache;

    /**
     * __constructor
     */
    public function __construct() {
        parent::__construct();
        $this->cache = Cache::getInstance(Cache::TYPE_MEMCACHED);
    }

    /**
     * set the isForce flag true if you want to insert a record with nothing
     * @param array $data_map
     * @param array $key_column
     * @param bool $is_force
     * @return false if failed
     */
    public function insert($data_map, $key_column = 'id', $is_force = false, $is_ignore = false) {

        if (!empty($data_map[$this->partition_column])) {
            if (!$this->checkPartition($data_map[$this->partition_column])) {
                $data_map[$this->partition_column] = $this->backup_partition_val;
            }
        }
        return parent::insert($data_map, $key_column, $is_force, $is_ignore);
    }
    /**
     * partition check
     * @return boolean
     */
    protected function checkPartition($data) {

        if(empty($data)) {
            throw new ModelException('partition data is empty');
        }
        $partition_cache_key = $this->getPartitionCacheKey($data);
        $cache_val = $this->cache->get($partition_cache_key);
        if ($cache_val == '1') return true;
        $create_table_str = $this->getCreateTableStr();
        if (!$create_table_str) {
            __add_info(sprintf('check partition error: %s table not exist', $this->table),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_DEBUG);
            return false;
        }
        if (!$this->checkIsPartitionSupported($create_table_str)) {
            if (!$this->convertPartitionSupported()) {
                __add_info(sprintf('check partition error: %s table do not support partition' , $this->table),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
                return false;
            }
            $create_table_str = $this->getCreateTableStr();
        }
        if (!$this->checkBackupPartition($create_table_str)) {
            if (!$this->addBackupPartition()) {
                __add_info(sprintf('add backup partition failed: %s', $this->table),
                    InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
                return false;
            }
        }
        $is_partition_exist = $this->_checkPartition($create_table_str, $data);
        if ($is_partition_exist) {
            $this->cache->set($partition_cache_key, '1', 90 * 24 * 3600);
        }
        return $is_partition_exist;
    }
    /**
     * get create table string
     */
    private function getCreateTableStr() {
        $table = $this->getTableName();
        $sql = "SHOW CREATE TABLE `{$table}`";
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
            __add_info(sprintf('check backup partition error: pattern not match. table: %s # %s' , $pattern, $create_table_str),
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
            __add_info(sprintf('add backup partition failed: type not supported %s, %s', $this->table, $this->partition_type),
                InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
            return false;
        }
        $sql = null;
        $table = $this->getTableName();
        switch ($this->partition_type) {
            case self::PARTITION_TYPE_LIST:
                $sql = "ALTER TABLE `{$table}`
                ADD PARTITION (PARTITION {$this->backup_partition} VALUES IN ({$this->backup_partition_val}))";
                break;
            case self::PARTITION_TYPE_RANGE:
                $sql = "ALTER TABLE `{$table}`
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
        __add_info(sprintf(
            'add backup partition %s: %s', $is_success ? 'success' : 'failed', $this->table),
            InfoCollector::TYPE_LOGIC, InfoCollector::LEVEL_INFO);
        return $is_success;
    }
    /**
     * get partition cache key
     * @param string $str
     */
    protected function getPartitionCacheKey($str) {
        return $this->table . '_partition_' . $str;
    }
    /**
     * check partition to know if it's need to add
     * @param string $create_table_str
     * @param string $data
     * @return bool
     */
    protected function _checkPartition($create_table_str, $data) {
        return preg_match("#PARTITION[^\(]+\({$data}\) ENGINE = [^\)\n]+#", $create_table_str);
    }
    /**
     * get range value for next partition
     * @param array | null $args
     * @return mixed
     */
    abstract protected function convertPartitionSupported();

}