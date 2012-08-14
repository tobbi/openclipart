<?php

class ArrayObjectFacade {
    protected $data;
    function __construct($arg) {
        $data = array();
        if ($arg !== null) {
            $this->data = $arg;
        }
    }
    function __get($name) {
        return $this->data[$name];
    }
    function exists($name) {
        return in_array($name, $this->data);
    }
    function get_data() {
        return $this->data;
    }
}