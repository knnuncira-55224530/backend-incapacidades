<?php

use Slim\App;
use App\Controllers\SeguimientoController;

return function (App $app) {
    $controller = new SeguimientoController();

    $app->get('/seguimientos', [$controller, 'index']);
    $app->post('/seguimientos', [$controller, 'store']);
    $app->patch('/seguimientos/{id}/estado', [$controller, 'updateStatus']);
};