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
 * driver interface
 * =======================================================
 * driver interface is defined here
 *
 * . queryAll($sql, $param = [], $fetch_associative = true)
 * . queryRow($sql, $param = [], $fetch_associative = true)
 * . queryColumn($sql, $param = [], $column_index)
 * . exec($sql, $param = [])
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package resource\db\driver
 * @version 1.0
 **/
namespace lightmvc\resource\db\driver;

interface DbDriverInterface
{

    /**
     * Executes the SQL statement and returns all rows.
     * @param $sql string
     * @param $param array
     * @param $fetch_associative
     */
    public function queryAll($sql, $param = [], $fetch_associative = true);
    /**
     * get the first row of the result set
     * @param string $sql
     * @param array $param
     * @param bool $fetch_associative
     * @return array false when failed
     */
    public function queryRow($sql, $param = [], $fetch_associative = true);
    /**
     * Executes the SQL statement and returns the {0_based_index} column of the result set.
     * @param string $sql
     * @param array $param
     * @param int $column_index
     * @return array the first column of the query result. Empty array if no result.
     */
    public function queryColumn($sql, $param = [], $column_index);
    /**
     * exec non-query commands like insert, delete, update, create table...
     * @param string $sql
     * @param array $param
     * @return insert: last_insert_id
     *         update: affected_rows
     */
    public function exec($sql, $param = [], $is_get_last_insert_id = false);

    /**
     * convert string to quoted string to avoid sql errors
     * @param string $value
     */
    public function quote($string);
    /**
     * get a connection instance
     * @return mixed depends on implementation
     */
    public function getConnection($config);
    /**
     * bind connection to driver
     * @param $connection
     */
    public function bindConnection($connection);

    /**
     * begin transaction
     */
    public function beginTransaction();
    /**
     * commit
     */
    public function commit();
    /**
     * rollback
     */
    public function rollback();
}
