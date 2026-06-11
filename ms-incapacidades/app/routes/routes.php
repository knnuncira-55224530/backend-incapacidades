<?php
declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\IncapacidadController;

return function (RouteCollectorProxy $app) {
    $controller = new IncapacidadController();
    
    $app->get('/incapacidades', [$controller, 'index']);
    $app->get('/incapacidades/estadisticas/resumen', [$controller, 'resumen']);
    $app->get('/incapacidades/empleado/{empleado_id}/activas', [$controller, 'activasPorEmpleado']);
    $app->get('/incapacidades/{id}', [$controller, 'show']);
    $app->post('/incapacidades', [$controller, 'store']);
    $app->put('/incapacidades/{id}', [$controller, 'update']);
    $app->patch('/incapacidades/{id}/estado', [$controller, 'cambiarEstado']);
    $app->patch('/incapacidades/{id}/finalizar', [$controller, 'finalizar']);
};