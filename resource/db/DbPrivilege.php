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
 * @package lightmvc\resource\db
 * @version 1.0
 **/
namespace lightmvc\resource\db;

abstract class DbPrivilege
{

    /**
     * check executable
     * @param string $sql
     */
    abstract public function checkExecutable($sql);
}
