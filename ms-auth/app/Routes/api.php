<?php

use Slim\App;
use App\Controllers\AuthController;

return function (App $app) {
    $c = new AuthController();

    $app->post('/login', [$c, 'login']);
    $app->post('/logout', [$c, 'logout']);
    $app->get('/validate', [$c, 'validate']);
};