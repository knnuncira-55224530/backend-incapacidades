<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\IncapacidadController;

return function (RouteCollectorProxy $app) {
    $controller = new IncapacidadController();
    
    $app->get('/incapacidades', [$controller, 'index']);
    $app->get('/incapacidades/{id}', [$controller, 'show']);
    $app->post('/incapacidades', [$controller, 'store']);
    $app->put('/incapacidades/{id}', [$controller, 'update']);
    $app->patch('/incapacidades/{id}/finalizar', [$controller, 'finalizar']);
};