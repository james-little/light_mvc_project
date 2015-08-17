<?php
namespace utility;

use \RecursiveDirectoryIterator,
    \RecursiveIteratorIterator;

/**
 * File
 * =======================================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/
class File {

    /**
     * @param       $dir
     * @param array $filter_file
     * @param null  $call_back
     * @return array
     */
    public function scanDirectory($dir, $filter_file = array(), $call_back = null) {

        if (empty($dir) || !is_readable($dir)) {
            return ;
        }
        $file_list = array();
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($dir)));
        $pattern = '';
        if(!empty($filter_file)) {
            $pattern = implode('|', $filter_file);
            $pattern = "#\.({$pattern})$#";
        }
        foreach($objects as $entry => $object) {
            if(preg_match('#(\.|\.\.)$#', $entry)) {
                continue;
            }
            if($pattern && preg_match($pattern, $entry)) {
                $this->addToList($entry, $file_list, $call_back);
                continue;
            }
            if (!$pattern) {
                $this->addToList($entry, $file_list, $call_back);
            }
        }
        return $file_list;
    }

    /**
     * add
     * @param      $object
     * @param      $list
     * @param null $call_back
     *
     * @return int
     */
    private function addToList($object, &$list, $call_back = null) {
        if($call_back) {
            call_user_func($call_back, $object);
        }
        return array_push($list, $object);
    }
}

