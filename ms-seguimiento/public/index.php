<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

require __DIR__ . '/../app/Config/database.php';

$app = AppFactory::create();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function (
    ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails
) {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'success' => false, 'message' => 'Recurso no encontrado'
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

$errorMiddleware->setErrorHandler(HttpMethodNotAllowedException::class, function (
    ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails
) {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'success' => false, 'message' => 'Metodo no permitido'
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(405);
});

$errorMiddleware->setDefaultErrorHandler(function (
    ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails
) {
    $response = new \Slim\Psr7\Response();
    $data = ['success' => false, 'message' => 'Error interno'];
    if ($displayErrorDetails) $data['detalle'] = $exception->getMessage();
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
});

$app->addBodyParsingMiddleware();

$app->options('/{routes:.+}', function (ServerRequestInterface $request, ResponseInterface $response) {
    return $response;
});

$app->add(function (ServerRequestInterface $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$routes = require __DIR__ . '/../app/Routes/routes.php';
$routes($app);  

$app->run();