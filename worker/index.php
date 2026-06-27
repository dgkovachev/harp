<?php
require __DIR__ . '/src/worker.php';

$worker = new \Worker\Worker();
$worker->run();

echo('reddy');