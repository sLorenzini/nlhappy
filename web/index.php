<?php

require_once __DIR__.'/../vendor/autoload.php';

$dbOptions = require_once __DIR__.'/../config/db.php';

/* Setup ORM */
use Illuminate\Database\Capsule\Manager as Capsule;
$capsule = new Capsule;
$capsule->addConnection($dbOptions);
$capsule->bootEloquent();

/* Setup Silex */
$app = new Silex\Application();
$app['debug'] = true;

/* Define Routes */
$app->post('/newsletters/new', function(){
    
});

$app->get('/', function(){
    return "Hi!";
});

$app->get('/hi/{name}', function($name){
    return "Hi, $name!";
});

/* Rock On! */
$app->run();