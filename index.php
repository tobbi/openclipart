<?php

require('libs/mustache.php/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

require_once('libs/Slim/Slim/Slim.php');

session_start();
$config = array();

//$config['db_user'] = 'root';
//$config['db_passord'] =
$config['root'] = 'http://localhost/ocal';

$app = new Slim();

$app->get('/login', function() use ($app) {

});
$app->post('/login', function() use ($app) {
    if (is_set($_POST['login']) && is_set($_POST['password'])) {

    }
});

$app->get("/register", function() use ($app) {

});

$app->get("/detail/:id/:link", function($id, $link) {

});

$app->get("/user-detail/:username", function($username) {
    
});

function get_data() {
    global $config;
    $data = array(
        "root" => $config['root'],
        //"username" => "jcubic",
        "librarian" => true
    );
    if (isset($_SESSION['username'])) {
        $data['username'] = $_SESSION['username'];
    }
    return $data;
}

function render_array_files($mustache, $templates, $data) {
    $result = array();
    foreach ($templates as $template) {
        $result[] = file_get_contents("templates/$template.template");
    }
    return $result;
}

$app->get('/', function() use ($app) {
    $main_template = file_get_contents('templates/main.template');
    $mustache = new Mustache_Engine(array(
        'escape' => function($val) { return $val; }
    ));
    $data = get_data();
    $sidebar = render_array_files($mustache, array('join'), $data);
    $data['sidebar'] = implode('\n', $sidebar);
    $content = render_array_files($mustache, array('wellcome'), $data);
    $data['content'] = implode('\n', $content);
    echo $mustache->render($main_template, $data);
});



$app->run();

?>
