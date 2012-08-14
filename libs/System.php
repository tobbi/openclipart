<?php
/**
 *  This file is part of Open Clipart Library <http://openclipart.org>
 *
 *  Open Clipart Library is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Open Clipart Library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Open Clipart Library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *  author: Jakub Jankiewicz <http://jcubic.pl>
 */


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