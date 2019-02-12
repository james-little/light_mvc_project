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
 *
 * Datatype
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

namespace lightmvc;

class Datatype
{

    const DATA_TYPE_INT    = 1;
    const DATA_TYPE_STRING = 2;
    const DATA_TYPE_FLOAT  = 3;
    const DATA_TYPE_BOOL   = 4;
    const DATA_TYPE_ARRAY  = 5;

    /**
     * convert data type
     * @param mixed $value
     * @param int $data_type
     * @return mixed
     */
    public static function convertDatatype($value, $data_type)
    {
        switch ($data_type) {
            case Datatype::DATA_TYPE_INT:
                return intval($value);
            case Datatype::DATA_TYPE_FLOAT:
                return floatval($value);
            case Datatype::DATA_TYPE_BOOL:
                return (bool) $value;
            default:
                return $value;
        }
    }
}
