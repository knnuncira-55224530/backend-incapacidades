<?php

use Slim\App;
use App\Controllers\EmpleadoController;

return function (App $app) {
    $c = new EmpleadoController();

    $app->get('/empleados', [$c, 'index']);
    $app->get('/empleados/{id}', [$c, 'show']);
    $app->post('/empleados', [$c, 'store']);
    $app->put('/empleados/{id}', [$c, 'update']);
    $app->patch('/empleados/{id}/estado', [$c, 'changeState']);
    $app->delete('/empleados/{id}', [$c, 'destroy']);
};