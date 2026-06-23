<?php
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$docRoot = __DIR__;
$file = $docRoot . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require $docRoot . '/index.php';
