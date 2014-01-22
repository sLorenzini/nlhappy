<?php

require_once __DIR__.'/../vendor/autoload.php';

$dbOptions = require_once __DIR__.'/../config/db.php';

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $dbOptions
));

$app->post('/newsletters/new', function(){
    
});

$app->get('/', function(){
    return "Hi!";
});

$app->get('/hi/{name}', function($name){
    return "Hi, $name!";
});

$app->run();