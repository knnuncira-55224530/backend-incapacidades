<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();
$capsule->addConnection(require __DIR__ . '/database.php');
$capsule->setAsGlobal();
$capsule->bootEloquent();