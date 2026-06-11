<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require __DIR__ . '/../app/Config/eloquent.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

(require __DIR__ . '/../app/Routes/api.php')($app);

$app->run();