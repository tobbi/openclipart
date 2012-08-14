<?php

class DatabaseException extends Exception {
    function __construct($msg) {
        Exception::__construct($msg, 100);
    }
}

class Database {
    function __construct($host, $user, $pass, $db) {
        $this->conn = new mysqli($host, $user, $pass);
        $this->conn->select_db($db);
        if ($this->conn->connect_errno) {
            throw new DatabaseException("Connect failed: " .
                                        $this->conn->connect_error);
        }
    }
    function get_array($query) {
        $result = array();
        $ret = $this->conn->query($query);
        if (!$ret) {
            throw new DatabaseException($this->conn->error);
        }
        while ($row = $ret->fetch_assoc()) {
            $result[] = $row;
        }
        $ret->close();
        return $result;
    }

    function get_value($query) {
        $result = array();
        $ret = $this->conn->query($query);
        if (!$ret) {
            throw new DatabaseException($this->conn->error);
        }
        $result = $ret->fetch_row();
        $ret->close();
        return $result[0];
    }
    function __call($method, $argv) {
        return call_user_func_array(array($this->conn, $method), $argv);
    }
}