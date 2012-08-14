<?php

require_once('config.php');

session_start();

$mysqli = new mysqli($config['db_host'],
                     $config['db_user'],
                     $config['db_pass']);

$mysqli->select_db($config['db_name']);

if ($mysqli->connect_errno) {
    echo "Connect failed: " . $mysqli->connect_error . "\n";
    exit();
}

$config['root'] = 'http://staging.openclipart.org';


$global = array(
    "root" => $config['root'],
    //"username" => "jcubic",
    "librarian" => true
);
if (isset($_SESSION['username'])) {
    $global['username'] = $_SESSION['username'];
}
