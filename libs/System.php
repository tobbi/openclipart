<?php

require_once('Slim/Slim/Slim.php');
require_once('Database.php');
require_once('ArrayObjectFacade.php');


class System {
    private $user_id;
    private $user_name;
    private $groups;
    public $config;
    public $db;
    function __construct($arg) {
        session_start();
        $this->slim = new Slim();
        if (is_callable($arg)) {
            $arg = $arg();
        }
        if (gettype($arg) !== 'array') {
            throw new Exception("System Argument need to be an array " .
                                "or a function that return an array");
        }
        $this->config = new ArrayObjectFacade($arg);
        $this->db = new Database($this->config->db_host,
                                 $this->config->db_user,
                                 $this->config->db_pass,
                                 $this->config->db_name);
        if (isset($_SESSION['userid'])) {
            // get user from database
        }
    }
    function login() {
    }
    function is_logged() {
        return $this->user_id != null;
    }
    function get_user_id() {
        return $this->user_id;
    }
    function get_user_name() {
        return $this->user_name;
    }
    function is_librarian() {
        return in_array('librarian', $this->groups);
    }
    function __call($method, $argv) {
        return call_user_func_array(array($this->slim, $method), $argv);
    }
}