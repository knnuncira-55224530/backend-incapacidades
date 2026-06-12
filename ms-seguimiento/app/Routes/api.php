<?php

use Slim\App;
use App\Controllers\SeguimientoController;

return function (App $app) {
    $controller = new SeguimientoController();

    $app->get('/seguimientos', [$controller, 'index']);
    $app->post('/seguimientos', [$controller, 'store']);
    $app->put('/seguimientos/{id}', [$controller, 'update']);
    $app->delete('/seguimientos/{id}', [$controller, 'destroy']);
};