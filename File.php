<?php

/**
 *  Copyright 2016 Koketsu
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
 * ==============================================================================
 *
 * file
 * @author koketsu <ketsu.ko@pokelabo.co.jp>
 * @version 1.1
 **/
namespace lightmvc;

class File
{

    const MKFILE_TYPE_MONTHLY = 'monthly';
    const MKFILE_TYPE_WEEKLY  = 'weekly';
    const MKFILE_TYPE_DAILY   = 'daily';
    const MKFILE_TYPE_HOURLY  = 'hourly';

    /**
     * @param       $dir
     * @param array $filter_file
     * @param null  $call_back
     *
     * @return array
     */
    public function scanDirectory($dir, $filter_file = [], $call_back = null, $max_depth = null)
    {
        if (empty($dir) || !is_readable($dir)) {
            return [];
        }
        $file_list = [];
        $objects   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(realpath($dir)));
        if (is_int($max_depth) && $max_depth > 0) {
            $objects->setMaxDepth($max_depth);
        }
        $pattern = '';
        if (!empty($filter_file)) {
            $pattern = implode('|', $filter_file);
            $pattern = "#\.({$pattern})$#";
        }
        foreach ($objects as $entry => $object) {
            if (preg_match('#(\.|\.\.)$#', $entry)) {
                continue;
            }
            if ($pattern && preg_match($pattern, $entry)) {
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
    private function addToList($object, &$list, $call_back = null)
    {
        if ($call_back) {
            call_user_func($call_back, $object);
        }
        return array_push($list, $object);
    }

    /**
     * make file by daily/monthly/weekly/hourly mode
     * @param string $base_dir
     * @param string $type
     * @return mixed
     *          false: failed
     *          string: file_name maded
     */
    public static function makeFile($base_dir, $type = self::MKFILE_TYPE_MONTHLY, $is_with_envi = false)
    {
        if (!in_array($type, [
            self::MKFILE_TYPE_DAILY,
            self::MKFILE_TYPE_MONTHLY,
            self::MKFILE_TYPE_HOURLY,
            self::MKFILE_TYPE_WEEKLY,
        ])) {
            return false;
        }
        if ($is_with_envi && defined('APPLICATION_ENVI')) {
            $base_dir .= '/' . APPLICATION_ENVI;
        }
        $file_name  = '';
        $sub_folder = '';
        switch ($type) {
            case self::MKFILE_TYPE_MONTHLY:
                $sub_folder = $base_dir;
                $file_name  = date('Ym') . '.log';
                break;
            case self::MKFILE_TYPE_WEEKLY:
                $sub_folder = "{$base_dir}/" . date('Ym');
                $file_name  = date('YW') . '.log';
                break;
            case self::MKFILE_TYPE_DAILY:
                $sub_folder = "{$base_dir}/" . date('Ym');
                $file_name  = date('Ymd') . '.log';
                break;
            case self::MKFILE_TYPE_HOURLY:
                $sub_folder = "{$base_dir}/" . date('Ym');
                $file_name  = date('YmdH') . '.log';
                break;
        }
        if (!is_dir($sub_folder)) {
            @mkdir($sub_folder, 0777, true);
            @chmod($sub_folder, 0777);
        }
        $full_path = $sub_folder . '/' . $file_name;
        @file_put_contents($full_path, '', FILE_APPEND);
        if (!is_file($full_path) || !is_writable($full_path)) {
            return $file_name;
        }
        return $full_path;
    }

    /**
     * parse csv file to array
     * @param string $filename
     * @param string $delimiter
     * @return array
     */
    public static function csvToArray($file_name, $delimiter = ',')
    {
        $handle = @fopen($file_name, 'r');
        if ($handle === false) {
            return [];
        }
        $data = [];
        while (($row = @fgetcsv($handle, 1000, $delimiter)) !== false) {
            $data[] = $row;
        }
        @fclose($handle);
        return $data;
    }

    /**
     * parse yaml file to array
     * @param string $filename
     * @return array | bool
     */
    public static function yamlToArray($file_name, $key = null)
    {
        if (empty($file_name)) {
            return [];
        }
        $result = null;
        if (Url::validateUrl($file_name) !== false) {
            $result = yaml_parse_url($file_name);
        } else {
            if (!is_file($file_name)) {
                return [];
            }
            $result = yaml_parse_file($file_name);
        }
        if ($key === null) {
            return $result;
        }
        return array_key_exists($key, $result) ? $result[$key] : [];
    }
    /**
     * get File info
     * @param  string $file
     * @return array
     */
    public static function getFileInfo($file)
    {
        $info_array = [
            'filename'  => '',
            'dirname'   => '',
            'extension' => '',
            'basename'  => '',
        ];
        if (empty($file)) {
            return $info_array;
        }
        $info                    = array_merge(['extension' => ''], pathinfo($file));
        $info_array['extension'] = $info['extension'];
        $info_array['filename']  = $info['filename'];
        $info_array['dirname']   = $info['dirname'];
        $info_array['basename']  = $info['basename'];
        return $info_array;
    }
}
