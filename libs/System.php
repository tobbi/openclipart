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
    private $original_config;
    public $config;
    private $nsfw;
    public $db;
    public $GET;
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
        $this->db = new Database($arg['db_host'],
                                 $arg['db_user'],
                                 $arg['db_pass'],
                                 $arg['db_name']);
        if (isset($_SESSION['userid'])) {
            // get user from database
        }
        // TODO: select user groups
        $this->groups = array();
        //query paramters that will be forward to urls
        $redirect =  'redirect=' . $arg['root'] .
            urlencode($_SERVER['REQUEST_URI']);
        if (isset($arg['forward_query_list'])) {
            $forward = filter_pair($_GET, function($k, $v) use($arg) {
                return in_array($k, $arg['forward_query_list']);
            });
            if (!empty($forward)) {
                $arg['forward_query'] = '?' . query_sring($forward);
                $arg['redirect'] = '&' . $redirect;
            } else {
                $arg['redirect'] = '?' . $redirect;
            }
        } else {
            $arg['redirect'] = '?' . $redirect;
        }
        $this->original_config = $arg;
        if ($this->can_overwrite_config()) {
            $arg = array_merge($arg, normalized_get_array());
        }
        $this->config = new ArrayObjectFacade($arg);
        $this->GET = new ArrayObjectFacade(normalized_get_array());
        if ($this->can_overwrite_user() && $this->config->exists('user')) {
            // disguise as user
        }
    }
    // can change to admin && developer
    // this user can act as other users using query string "user=<ID>"
    // so it can test how the site work for this other user
    function can_overwrite_user() {
        return $this->is_admin();
    }
    function can_overwrite_config() {
        return $this->is_admin();
    }
    // this user can set data passed to mustache via query string
    function can_overwrite_mustache() {
        return $this->is_admin();
    }
    function nsfw() {
        if ($this->is_logged()) {
            return $this->config->get('nsfw', $this->nfsw);
        } else {
            return true;
        }
    }
    function track() {
        return $this->GET->get('track', true);
    }
    function config_array() {
        return $this->original_config;
    }
    function login($username, $password) {
        //$this->db->
    }
    function is_logged() {
        return $this->user_id != null;
    }
    function get_user_id() {
        return $this->user_id;
    }
    function is_admin() {
        //debug
        return true;
        return $this->is_logged() && in_array('admin', $this->groups);
    }
    function get_user_name() {
        return $this->user_name;
    }

    function is_librarian() {
        return $this->is_logged() && in_array('librarian', $this->groups);
    }
    function __call($method, $argv) {
        return call_user_func_array(array($this->slim, $method), $argv);
    }
}