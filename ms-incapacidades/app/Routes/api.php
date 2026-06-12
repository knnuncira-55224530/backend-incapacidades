<?php

use Slim\App;
use App\Controllers\IncapacidadController;

return function (App $app) {
    $c = new IncapacidadController();

    $app->options('/{routes:.+}', function ($request, $response) {
        return $response;
    });

    $app->get('/incapacidades', [$c, 'index']);
    $app->get('/incapacidades/{id}', [$c, 'show']);
    $app->post('/incapacidades', [$c, 'store']);
    $app->put('/incapacidades/{id}', [$c, 'update']);
    $app->patch('/incapacidades/{id}/finalizar', [$c, 'finalize']);
    $app->delete('/incapacidades/{id}', [$c, 'destroy']);
};