<?php

use Slim\App;
use App\Controllers\EmpleadoController;

return function (App $app) {
    $controller = new EmpleadoController();

    $app->get('/empleados', [$controller, 'index']);
    $app->post('/empleados', [$controller, 'store']);
    $app->put('/empleados/{id}', [$controller, 'update']);
    $app->patch('/empleados/{id}/estado', [$controller, 'changeStatus']);
};