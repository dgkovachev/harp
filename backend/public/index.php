<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/../bootstrap/container.php';
AppFactory::setContainer($container);

$app = require __DIR__ . '/../bootstrap/app.php';

$app->run();
