<?php

require('libs/mustache.php/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

require_once('libs/Slim/Slim/Slim.php');


$config = array();

//$config['db_user'] = 'root';
//$config['db_passord'] = 
$config['root'] = 'http://localhost/ocal';

$app = new Slim();

$app->get('/', function() use ($app) {
    global $config;
    $main_template = file_get_contents('templates/main.template');
    $mustache = new Mustache_Engine;
    $data = array(
        "root" => $config['root'],
        //"username" => "jcubic",
        "librarian" => true
    );
    echo $mustache->render($main_template, $data);
});

$app->get('/login', function() use ($app) {
    
});
$app->post('/login', function() use ($app) {
    if (is_set($_POST['login']) && is_set($_POST['password'])) {
        
    }
});

$app->run();

?>
