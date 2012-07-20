<?php

require('libs/mustache.php/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

require_once('libs/Slim/Slim/Slim.php');

session_start();
//require('config.php');

//mysql_connect($config['db_host'], $config['db_user'], $config['db_pass']);
//@mysql_select_db($config['db_name']) or die("Unable to select database");

$config['root'] = 'http://staging.openclipart.org';

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
    $common = get_data();
    $sidebar = implode("\n", render_array_files($mustache,
                                                array('join', 'facebook_box'),
                                                $common));
    $content = implode("\n", render_array_files($mustache,
                                                array('wellcome'),
                                                $common));
    $data = array_merge($common, array('sidebar' => $sidebar,
                                       'content' => $content));
    echo $mustache->render($main_template, $data);
});


$app->run();

?>
