<?php

$project = null;
if (isset($argv[1])) {
    $project = $argv[1];
}
if (empty($project)) {
    echo 'project name not specified!';
    exit(0);
}
if (preg_match('#\/#', $project)) {
    echo "project name should not contain '/' or '\' !";
    exit(0);
}

$base_path = null;
if (isset($argv[2])) {
    $base_path = $argv[2];
}
if (empty($base_path)) {
    echo 'base path not specified!';
    exit(0);
}
if (!file_exists($base_path)) {
    echo 'base path not exist!';
    exit(0);
}
$base_path = preg_replace('#/$#', '', $base_path);
set_time_limit(0);
$base_path = $base_path . '/' . $project;
// make project folder
makeDir($base_path);
// make sub folder recursively
$string = '0123456789abcdefghijklmnopqrstuvwxyz';
$length = strlen($string);
for($i = 0; $i < $length; $i++) {
    for($j = 0; $j < $length; $j++) {
        makeDir($base_path . '/' . $string[$i] . '/' . $string[$j]);
    }
}

function makeDir($param) {
    if(!file_exists($param)) {
        makeDir(dirname($param));
        @mkdir($param, 0777);
        echo "[OK]: {$param}\n";
    }
}