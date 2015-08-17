<?php

namespace resource\db;

/**
 * DB privilege
 * =======================================================
 * This class is a abstract for database privilege
 *
 * Example:
 *
 *     $db = Db::getInstance($db_config);
 *     $sql = 'UPDATE table SET user_id = 401 WHERE id = :id';
 *     $result = $db->getCommand()->exec($sql, array(':id' => 17));
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package resource\db
 * @version 1.0
 **/


abstract class DbPrivilege {

    /**
     * check executable
     * @param string $sql
     */
    abstract function checkExecutable($sql);
}