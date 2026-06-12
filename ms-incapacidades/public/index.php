<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use App\Middleware\CorsMiddleware;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require __DIR__ . '/../app/Config/eloquent.php';

$app = AppFactory::create();
$app->add(new CorsMiddleware());
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../app/Routes/api.php')($app);

$app->run();