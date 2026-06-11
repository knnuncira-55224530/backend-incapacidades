<?php

use Slim\App;
use App\Controllers\IncapacidadController;

return function (App $app) {
    $controller = new IncapacidadController();

    $app->get('/incapacidades', [$controller, 'index']);
    $app->post('/incapacidades', [$controller, 'store']);
    $app->put('/incapacidades/{id}', [$controller, 'update']);
    $app->patch('/incapacidades/{id}/finalizar', [$controller, 'finalize']);
};