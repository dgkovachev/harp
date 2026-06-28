<?php
require __DIR__ . '/../vendor/autoload.php';
$classes = ["App\PDO_CON", "App\REDIS_CON", "App\Authentication", "App\RedisService", "App\TokenService", "App\Router"];
foreach ($classes as $c) {
    if (!class_exists($c)) {
        fwrite(STDERR, "ERROR: $c not found\n");
        exit(1);
    }
}
echo "All classes autoload OK\n";
