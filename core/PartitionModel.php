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
 * partition model (DB)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
namespace lightmvc\core;

use lightmvc\core\Model;
use lightmvc\exception\ModelException;
use lightmvc\info\InfoCollector;
use lightmvc\resource\cache\Cache;

abstract class PartitionModel extends Model
{

    const PARTITION_TYPE_LIST  = 1;
    const PARTITION_TYPE_RANGE = 2;

    // range data per partition when using range partition
    protected $range_data_per_partition = 400000;
    protected $partition_type;
    protected $partition_column     = 'partition_flag';
    protected $backup_partition     = 'pbak';
    protected $backup_partition_val = 0;
    protected $cache;
    private $is_pk_autoincrement = false;

    /**
     * __constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->cache = Cache::getInstance();
    }

    /**
     * set the isForce flag true if you want to insert a record with nothing
     * @param array $data_map
     * @param bool $is_force
     * @param bool $is_ignore
     * @return false if failed
     */
    public function insert($data_map, $is_force = false, $is_ignore = false)
    {
        if (!isset($data_map[$this->partition_column])) {
            $data_map[$this->partition_column] = $this->getPartitionVal($data_map);
        }
        if (!$this->checkPartition($data_map[$this->partition_column], true)) {
            $data_map[$this->partition_column] = $this->backup_partition_val;
        }
        if ($this->pk == $this->partition_column && $this->is_pk_autoincrement) {
            unset($data_map[$this->pk]);
        }
        return parent::insert($data_map, $is_force, $is_ignore);
    }
    /**
     * partition check
     * @return boolean
     */
    protected function checkPartition($data, $is_insert = false)
    {
        if (!$is_insert && empty($data)) {
            throw new ModelException('partition data is empty');
        }
        if (!empty($data)) {
            $partition_cache_key = $this->getPartitionCacheKey($data);
            $cache_val           = $this->cache->get($partition_cache_key);
            if ($cache_val == '1') {
                return true;
            }
        }
        $create_table_str          = $this->getCreateTableStr();
        $this->is_pk_autoincrement = $this->checkIsPkAutoincrement($create_table_str);
        if (!$create_table_str) {
            __add_info(
                sprintf(
                    'check partition error: %s table not exist',
                    $this->table
                ),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        if (!$this->checkIsPartitionSupported($create_table_str)) {
            if (!$this->convertPartitionSupported()) {
                __add_info(
                    sprintf(
                        'check partition error: %s table do not support partition',
                        $this->table
                    ),
                    InfoCollector::TYPE_LOGIC,
                    InfoCollector::LEVEL_INFO
                );
                return false;
            }
            $create_table_str = $this->getCreateTableStr();
        }
        if (!$this->checkBackupPartition($create_table_str)) {
            if (!$this->addBackupPartition()) {
                __add_info(
                    sprintf(
                        'add backup partition failed: %s',
                        $this->table
                    ),
                    InfoCollector::TYPE_LOGIC,
                    InfoCollector::LEVEL_INFO
                );
                return false;
            }
        }
        $is_partition_exist = $this->_checkPartition($create_table_str, $data);
        if ($is_partition_exist && !empty($data)) {
            $this->cache->set($partition_cache_key, '1', 90 * 24 * 3600);
        }
        return $is_partition_exist;
    }
    /**
     * get create table string
     */
    private function getCreateTableStr()
    {
        $table  = $this->getTableName();
        $sql    = "SHOW CREATE TABLE `{$table}`";
        $result = $this->queryRow($sql);
        return empty($result) ? false : $result['Create Table'];
    }
    /**
     * check if table supported partition
     * @param string $create_table_str
     * @return bool
     */
    protected function checkIsPartitionSupported($create_table_str)
    {
        return (bool) preg_match('#PARTITION BY#', $create_table_str);
    }
    /**
     * check if primary key is autoincrement
     * @param string $create_table_str
     * @return bool
     */
    protected function checkIsPkAutoincrement($create_table_str)
    {
        if (is_array($this->pk)) {
            return false;
        }
        return (bool) preg_match("#`{$this->pk}`.*AUTO_INCREMENT.*,#i", $create_table_str);
    }
    /**
     * check backup partition
     * @param string $create_table_str
     */
    protected function checkBackupPartition($create_table_str)
    {
        $pattern = "#PARTITION {$this->backup_partition}[^\n]+VALUES[^\n]+ENGINE = [^,]+#";
        if (!preg_match($pattern, $create_table_str)) {
            __add_info(
                sprintf(
                    'check backup partition error: pattern not match. table: %s # %s',
                    $pattern,
                    $create_table_str
                ),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_DEBUG
            );
            return false;
        }
        return true;
    }
    /**
     * check backup partition
     * @param string $create_table_str
     */
    protected function addBackupPartition()
    {
        if (empty($this->partition_type)
            || !in_array($this->partition_type, array(self::PARTITION_TYPE_LIST, self::PARTITION_TYPE_RANGE))) {
            // partition type not support
            __add_info(
                sprintf(
                    'add backup partition failed: type not supported %s, %s',
                    $this->table,
                    $this->partition_type
                ),
                InfoCollector::TYPE_LOGIC,
                InfoCollector::LEVEL_INFO
            );
            return false;
        }
        $sql   = null;
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
        $count      = 3;
        while ($count) {
            $is_success = $this->exec($sql);
            if ($is_success) {
                break;
            }
            $count--;
        }
        __add_info(
            sprintf(
                'add backup partition %s: %s',
                $is_success ? 'success' : 'failed',
                $this->table
            ),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_INFO
        );
        return $is_success;
    }
    /**
     * get partition cache key
     * @param string $str
     */
    protected function getPartitionCacheKey($str)
    {
        return $this->table . '_partition_' . $str;
    }
    /**
     * check partition to know if it's need to add
     * @param string $create_table_str
     * @param string $data
     * @return bool
     */
    protected function _checkPartition($create_table_str, $data)
    {
        return preg_match("#PARTITION[^\(]+\({$data}\) ENGINE = [^\)\n]+#", $create_table_str);
    }
    /**
     * bind partiion column into condition array(with "=")
     * @param  array      $condition
     * @param  string|int $val
     * @return array
     */
    protected function bindPartitionCondition($condition, $val)
    {
        if (empty($condition)) {
            return $condition;
        }
        $condition["{$this->partition_column} = "] = $val;
        return $condition;
    }
    /**
     * bind partition column with between condition
     * @param  array  $condition
     * @param  int|string  $start
     * @param  int|string  $end
     * @param  bool $with_equal
     * @return array
     */
    protected function bindBetweenPartitionCondition($condition, $start, $end, $with_equal = false)
    {
        if (empty($condition)) {
            return $condition;
        }
        if (!$start && !$end) {
            return $condition;
        }
        $equal = $with_equal ? '=' : '';

        if ($start) {
            $condition["{$this->partition_column} >{$equal} "] = $start;
        }
        if ($end) {
            $condition["{$this->partition_column} <{$equal} "] = $end;
        }
        return $condition;
    }
    /**
     * get range value for next partition
     * @param array | null $args
     * @return mixed
     */
    abstract protected function convertPartitionSupported();
    /**
     * get partition flat value
     * @param array | null $data_map
     * @return string
     */
    abstract protected function getPartitionVal($data_map);
}
