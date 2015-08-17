<?php

/**
 * Datatype
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class Datatype {

    const DATA_TYPE_INT = 1;
    const DATA_TYPE_STRING = 2;
    const DATA_TYPE_FLOAT = 3;
    const DATA_TYPE_BOOL = 4;
    const DATA_TYPE_ARRAY = 5;


    /**
     * convert data type
     * @param mixed $value
     * @param int $data_type
     * @return mixed
     */
    static public function convertDatatype($value, $data_type) {
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