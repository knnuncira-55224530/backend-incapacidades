<?php
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

return function (RouteCollectorProxy $app) {
    $authController = new AuthController();

    // Rutas públicas
    $app->post('/login', [$authController, 'login']);
    
    // Rutas protegidas
    $app->group('', function (RouteCollectorProxy $group) use ($authController) {
        $group->post('/logout', [$authController, 'logout']);
        $group->get('/validar', [$authController, 'validar']);
    })->add(new AuthMiddleware());
};