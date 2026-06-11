<?php

use Slim\App;
use App\Controllers\AuthController;

return function (App $app) {
    $auth = new AuthController();
    $app->post('/login', [$auth, 'login']);
    $app->post('/logout', [$auth, 'logout']);
    $app->get('/validate-session', [$auth, 'validateSession']);
};