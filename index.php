<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', 'On');

require_once('libs/Slim/Slim/Slim.php');
require_once('bootstrap.php');

require_once('libs/utils.php');
require_once('libs/Template.php');

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


$app->get('/', function() {
    echo "<!--\n"; // comment all debug strings
    $main = new Template('main', function() {
        return array('content' =>
                     array(new Template('wellcome', null),
                           new Template('most_popular_thumbs', function() {
                               return array(
                                   'content' => new Template('clipart_list', function() {
                                       global $global, $mysqli;
                                       if (isset($global['nsfw']) && $global['nsfw']) {
                                           $nsfw = '';
                                       } else {
                                           $nsfw = 'nsfw = 0';
                                       }
                                       if (isset($global['userid'])) {
                                           $fav_check = $global['userid'] . ' in (SELECT'.
                                               'user FROM openclipart_favorites WHERE op'.
                                               'enclipart_clipart.id = clipart)';
                                       } else {
                                           $fav_check = '0';
                                       }
                                       $query = "SELECT openclipart_clipart.id, title, filename, link, created, user_name, count(DISTINCT user) as num_favorites, created, date, 0 as user_fav FROM openclipart_clipart INNER JOIN openclipart_favorites ON clipart = openclipart_clipart.id INNER JOIN openclipart_users ON openclipart_users.id = owner WHERE openclipart_clipart.id NOT IN (SELECT clipart FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag WHERE clipart = openclipart_clipart.id AND openclipart_tags.name = 'pd_issue') AND (SELECT WEEK(max(date)) FROM openclipart_favorites) = WEEK(date) AND YEAR(NOW()) = YEAR(date) GROUP BY openclipart_clipart.id ORDER BY num_favorites DESC LIMIT 8";
                                       $ret = $mysqli->query($query);
                                       if (!$ret) {
                                           die($mysqli->error);
                                       }
                                       $clipart_list = array();
                                       while ($row = $ret->fetch_assoc()) {
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
                                       $ret->close();
                                       return array('cliparts' => $clipart_list);
                                   })
                               );
                           })
                     ),
                     'sidebar' => array(
                         new Template('join', null),
                         new Template('facebook_box', null),
                         new Template('tag_cloud', function() {
                             $TAG_LIMIT = 100;
                             $query = "SELECT count(openclipart_tags.id) as tag_count  FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT 1";
                             $max = mysqli_get_value($query);
                             $query = "SELECT openclipart_tags.name, count(openclipart_tags.id) as tag_count FROM openclipart_clipart_tags INNER JOIN openclipart_tags ON openclipart_tags.id = tag GROUP BY tag ORDER BY tag_count DESC LIMIT " . $TAG_LIMIT;
                             $result = array();
                             $ret = mysqli_get_array($query);
                             echo '<<<<<<<' . count($ret) . ">>>>>>>>>>>\n";
                             $normalize = size('20', $max);
                             foreach ($ret as $row) {
                                 //$size = round(($row['tag_count'] * 100) / $max, 0);
                                 $result[] = array(
                                     'name' => $row['name'],
                                     'size' => $normalize($row['tag_count'])
                                 );
                             }
                             shuffle($result);
                             return array('tags' => $result);
                         })
                     )
        ); //array('content'
    }); // new Template('main'
    $start_time = get_time();
    $result = $main->render();
    echo "-->"; // close debug
    echo $result;
    // load time
	$end_time = sprintf("%.4f", (get_time()-$start_time));
    echo "\n <!-- Time: $end_time seconds -->";
});

$app->get('/image/:width/:user/:filename', function($w, $user, $file) use ($app) {
    $width = intval($w);
    $response = $app->response();
    $response['Content-Type'] = 'image/png';
    $root_dir = dirname(__FILE__);
    $png = "$root_dir/people/$user/${width}px-$file";
    $svg = "$root_dir/people/$user/" . preg_replace("/.png$/", '.svg', $file);
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
});

$app->post('/rpc/:name', function($name) use ($app) {
    $root_dir = dirname(__FILE__);
    $filename = "$root_dir/rpc/".$name.".php";
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
