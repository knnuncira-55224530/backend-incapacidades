<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\SeguimientoController;

return function (RouteCollectorProxy $app) {
    $controller = new SeguimientoController();
    
    $app->get('/seguimientos', [$controller, 'index']);
    $app->get('/seguimientos/{id}', [$controller, 'show']);
    $app->post('/seguimientos', [$controller, 'store']);
    
    // Historial completo de una incapacidad específica
    $app->get('/seguimientos/incapacidad/{incapacidad_id}', [$controller, 'historialIncapacidad']);
};