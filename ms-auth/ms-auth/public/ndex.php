<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;

// ============================================
// CARGAR CONFIGURACIÓN
// ============================================

// Cargar variables de entorno
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Cargar configuración de base de datos (Eloquent ORM)
require __DIR__ . '/../app/Config/database.php';

// ============================================
// CREAR APLICACIÓN SLIM
// ============================================

$app = AppFactory::create();

// ============================================
// MIDDLEWARE: MANEJO DE ERRORES
// ============================================

// Middleware para capturar errores y devolver JSON
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Manejador personalizado para errores 404
$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function (
    ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetails
) {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'success' => false,
        'message' => 'Recurso no encontrado',
        'ruta' => $request->getUri()->getPath()
    ]));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(404);
});

// Manejador personalizado para métodos no permitidos
$errorMiddleware->setErrorHandler(HttpMethodNotAllowedException::class, function (
    ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetails
) {
    $response = new \Slim\Psr7\Response();
    $response->getBody()->write(json_encode([
        'success' => false,
        'message' => 'Método no permitido',
        'metodo' => $request->getMethod()
    ]));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(405);
});

// Manejador genérico para errores del servidor
$errorMiddleware->setDefaultErrorHandler(function (
    ServerRequestInterface $request,
    \Throwable $exception,
    bool $displayErrorDetails
) {
    $response = new \Slim\Psr7\Response();
    
    $data = [
        'success' => false,
        'message' => 'Error interno del servidor'
    ];
    
    // Solo mostrar detalles en desarrollo
    if ($displayErrorDetails) {
        $data['detalle'] = $exception->getMessage();
        $data['archivo'] = $exception->getFile();
        $data['linea'] = $exception->getLine();
    }
    
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(500);
});

// ============================================
// MIDDLEWARE: PARSEAR BODY JSON
// ============================================

$app->addBodyParsingMiddleware();

// ============================================
// MIDDLEWARE: CORS (Cross-Origin Resource Sharing)
// ============================================

// Manejar peticiones OPTIONS (preflight)
$app->options('/{routes:.+}', function (ServerRequestInterface $request, ResponseInterface $response) {
    return $response;
});

// Middleware CORS
$app->add(function (ServerRequestInterface $request, $handler) {
    $response = $handler->handle($request);
    
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-Auth-Token')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Expose-Headers', 'X-Auth-Token')
        ->withHeader('Access-Control-Max-Age', '86400');
});

// ============================================
// MIDDLEWARE: LOGGING DE PETICIONES (opcional, para desarrollo)
// ============================================

$app->add(function (ServerRequestInterface $request, $handler) {
    $method = $request->getMethod();
    $uri = $request->getUri()->getPath();
    $timestamp = date('Y-m-d H:i:s');
    
    // Log simple en consola/archivo
    error_log("[{$timestamp}] {$method} {$uri}");
    
    return $handler->handle($request);
});

// ============================================
// CARGAR RUTAS
// ============================================

$routes = require __DIR__ . '/../app/Routes/routes.php';
$routes($app);

// ============================================
// EJECUTAR APLICACIÓN
// ============================================

$app->run();