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

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', 'On');

define('DEBUG', true);

require_once('libs/utils.php');
require_once('libs/Template.php');
require_once('libs/System.php');

/* TODO: logs (using slim) and cache in Template::render
 *
 *
 *
 */

$app = new System(function() {
    /*
     *  System config need those variables
     *  db_user, db_host, db_pass, db_name
     */
    $config = get_object_vars(json_decode(file_get_contents('config.json')));
    return array_merge($config, array(
        'root' => 'http://staging.openclipart.org',
        'root_directory' => dirname(__FILE__),
        'tag_limit' => 100,
        'top_artist_last_month_limit' => 10,
        'home_page_thumbs_limit' => 8,
        'home_page_collections_limit' => 5,
        'home_page_news_limit' => 3,
        'bitmap_resolution_limit' => 3840,
        'google_analytics' => false,
        'show_facebook' => false,
        'debug' => true,
        'track_download' => true
    ));
});

$app->notFound(function () use ($app) {
    $response = $app->response();
    $response['Content-Type'] = 'text/html';
    $main = new Template('main', function() {
        return array('content' => new Template('error_404', null));
    });
    echo $main->render();
});

$app->error(function(Exception $e) {
    echo 'error';
});

$app->post('/login', function() use ($app) {
    if (is_set($_POST['login']) && is_set($_POST['password'])) {

    }
});

$app->get("/register", function() use ($app) {

});

$app->get("/detail/:id/:link", function($id, $link) use ($app) {

});

$app->get("/user-detail/:username", function($username) use ($app) {

});


function create_thumbs($where, $order_by) {
    global $app;
    if ($app->config->exists('nsfw') &&
        $app->config->nsfw) {
        $nsfw = '';
    } else {
        $nsfw = 'nsfw = 0';
    }
    if ($app->is_logged()) {
        $fav_check = $app->get_user_id() . ' in '.
            '(SELECT user FROM openclipart_favorites'.
            ' WHERE openclipart_clipart.id = clipart)';
    } else {
        $fav_check = '0';
    }
    if ($where != '' && $where != null) {
        $where = "AND $where";
    }
    $query = "SELECT openclipart_clipart.id, title, filename, link, created, username, count(DISTINCT user) as num_favorites, created, date, $fav_check as user_favm, downloads FROM openclipart_clipart INNER JOIN openclipart_favorites ON clipart = openclipart_clipart.id INNER JOIN openclipart_users ON openclipart_users.id = owner WHERE openclipart_clipart.id NOT IN (SELECT clipart FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag WHERE clipart = openclipart_clipart.id AND openclipart_tags.name = 'pd_issue') $where GROUP BY openclipart_clipart.id ORDER BY $order_by DESC LIMIT " . $app->config->home_page_thumbs_limit;
    $clipart_list = array();
    foreach ($app->db->get_array($query) as $row) {
        $filename_png = preg_replace("/.svg$/",
                                     ".png",
                                     $row['filename']);
        $human_date = human_date($row['created']);
        $data = array(
            'filename_png' => $filename_png,
            'human_date' => $human_date
            //TODO: check when close this query
            //'user_fav' => false
        );
        $clipart_list[] = array_merge($row, $data);
    }
    return array('cliparts' => $clipart_list);
}



$app->get('/', function() {
    global $app;
    $main = new Template('main', function() {
        return array('content' =>
                     array(new Template('wellcome', null),
                           new Template('most_popular_thumbs', function() {
                               return array(
                                   'content' => new Template('clipart_list', function() {
                                       $last_week = "(SELECT WEEK(max(date)) FROM ".
                                           "openclipart_favorites) = WEEK(date) AND ".
                                           "YEAR(NOW()) = YEAR(date)";
                                       return create_thumbs($last_week, "num_favorites");
                                   })
                               );
                           }),
                           new Template('new_clipart_thumbs', function() {
                               return array(
                                   'content' => new Template('clipart_list', function() {
                                       return create_thumbs(null, "created");
                                   })
                               );
                           }),
                           new Template('top_download_thumbs', function() {
                               return array(
                                   'content' => new Template('clipart_list', function() {
                                       $top_download = "YEAR(created) = YEAR(CURRENT_".
                                           "DATE) AND MONTH(created) = MONTH(CURRENT_".
                                           "DATE)";
                                       return create_thumbs($top_download, "downloads");
                                   })
                               );
                           })
                     ),
                     'sidebar' => array(
                         new Template('join', null),
                         new Template('facebook_box', null),
                         new Template('follow_us_box', null),
                         new Template('news_box', function() {
                             global $app;
                             $query = "SELECT link, title FROM openclipart_news ORDER by date DESC LIMIT " . $app->config->home_page_news_limit;
                             return array('news' =>
                                          array_reverse($app->db->get_array($query)));
                         }),
                         new Template('tag_cloud', function() {
                             global $app;
                             $query = "SELECT count(openclipart_tags.id) as tag_count  FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT 1";
                             $max = $app->db->get_value($query);
                             $query = "SELECT openclipart_tags.name, count(openclipart_tags.id) as tag_count FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT " . $app->config->tag_limit;
                             $result = array();
                             $rows = $app->db->get_array($query);
                             shuffle($rows);
                             $normalize = size('20', $max);
                             return array('tags' =>
                                          array_map(function($row) use ($normalize) {
                                              return array(
                                                  'name' => $row['name'],
                                                  'size' => $normalize($row['tag_count'])
                                              );
                                          }, $rows)
                             );
                         }),
                         new Template('top_artists_last_month', function() {
                             global $app;
                             $query = "SELECT full_name, username, count(filename) AS num_uploads FROM openclipart_clipart INNER JOIN openclipart_users ON owner = openclipart_users.id  WHERE date_format(created, '%Y-%c') = date_format(now(), '%Y-%c') GROUP BY openclipart_users.id ORDER BY num_uploads DESC LIMIT " . $app->config->top_artist_last_month_limit;
                             return array('artists' => $app->db->get_array($query));
                         }),
                         new Template('latest_collections_box', function() {
                             global $app;
                             $query = "SELECT openclipart_collections.id, name, title, username, date FROM openclipart_collections INNER JOIN openclipart_users ON user = openclipart_users.id ORDER BY date DESC LIMIT " . $app->config->home_page_collections_limit;
                             return array('collections' => array_map(function($row) {
                                 return array_merge($row, array(
                                     'human_date' => human_date($row['date'])
                                 ));
                             }, $app->db->get_array($query)));

                         })
                     )
        ); //array('content'
    }); // new Template('main'
    echo $main->render();
});

$app->get('/test/:x', function($x) {
    echo $x;
    return;
    $main = new Template('test', function() {
        return array('foo' => function($query) {
            global $app;
            $array = $app->db->get_array($query);
            return implode(' | ', $array[0]);
        });
    });
    echo $main->render();
});

$app->get('/clipart/:id/:link', function($id, $link) {
    $main = new Template('main', function() {
        return array('content' => array(
            new Template('clipart_detail', function() {
                
                $tags = "SELECT ";
                //editable - librarian or clipart owner
            })
        ));
    });
    return $main->render();
});

// routing /people/*.svg
$app->get('/download/:user/:filename', function($user, $filename) {
    global $app;
    $svg = $app->config->root_directory . "/people/" . $user . "/" . $filename;
    if (!file_exists($svg) || filesize($svg) == 0) { // old OCAL have some 0 size files
        $app->notFound();
    } else {
        $response = $app->response();
        $response['Content-Type'] = 'application/octet-stream';
        if ($app->config->track_download) {
            $user = $app->db->escape($user);
            $filename = $app->db->escape($filename);
            $query = "UPDATE openclipart_clipart SET downloads = downloads + 1 WHERE owner = (SELECT id FROM openclipart_users WHERE username = '$user') AND filename = '$filename'";
            $app->db->query($query);
        }
        echo file_get_contents($svg);
    }
});


$app->get('/image/:width/:user/:filename', function($w, $user, $file) {
    global $app;
    $width = intval($w);
    $png = $app->config->root_directory . "/people/$user/${width}px-$file";
    $svg = $app->config->root_directory . "/people/$user/" .
        preg_replace("/.png$/", '.svg', $file);
    $response = $app->response();
    if ($width > $app->config->bitmap_resolution_limit) {
        $response->status(400);
        // TODO: error template
        echo "Resolution couldn't be higher then 3840px! Please download SVG and produce such huge bitmap locally.";
    } else if (!file_exists($svg) || filesize($svg) == 0) {
        // NOTE: you don't need to check user and file for script injection because
        //       file_exists will prevent this
        $app->notFound();
    } else {
        $response['Content-Type'] = 'image/png';
        if (file_exists($png)) {
            echo file_get_contents($png);
        } else {
            exec("rsvg --width $width $svg $png");
            if (!file_exists($png)) {
                $response['Content-Type'] = "text/html";
                $app->pass();
            } else {
                echo file_get_contents($png);
            }
        }
    }
});

$app->post('/rpc/:name', function($name) use ($app) {
    $filename = $app->config->root_directory . "/rpc/".$name.".php";
    require('libs/json-rpc/json-rpc.php');
    if (class_exists($name)) {
        handle_json_rpc(new $name());
    } else {
        if (file_exists($filename)) {
            require_once($filename);
            handle_json_rpc(new $name());
        } else {
            $msg = "ERROR: service `$name' not found";
            echo json_encode(array(
                "error" => array("code" => 108, "message" => $msg)
            ));
        }
    }
});

$app->run();




?>
