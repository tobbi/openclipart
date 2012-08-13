<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', 'On');

require('libs/mustache.php/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

require_once('libs/Slim/Slim/Slim.php');

session_start();
require('config.php');

//mysql_connect($config['db_host'], $config['db_user'], $config['db_pass']);
//@mysql_select_db($config['db_name']) or die("Unable to select database");

$mysqli = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
$mysqli->select_db($config['db_name']);

if ($mysqli->connect_errno) {
    echo "Connect failed: " . $mysqli->connect_error . "\n";
    exit();
}

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



function human_date($date) {
    $timestamp = strtotime($date);
    if ($timestamp >= strtotime("-1 minutes"))
        return "1 minute ago";
    if ($timestamp >= strtotime("-2 minutes"))
        return "2 minutes ago";
    if ($timestamp >= strtotime("-3 minutes"))
        return "3 minutes ago";
    if ($timestamp >= strtotime("-4 minutes"))
        return "4 minutes ago";
    if ($timestamp >= strtotime("-5 minutes"))
        return "5 minutes ago";
    if ($timestamp >= strtotime("-10 minutes"))
        return "10 minutes ago";
    if ($timestamp >= strtotime("-30 minutes"))
        return "half an hour ago";
    if ($timestamp >= strtotime("-1 hours"))
        return "1 hour ago";
    if ($timestamp >= strtotime("-2 hours"))
        return "2 hours ago";
    if ($timestamp >= strtotime("-3 hours"))
        return "3 hours ago";
    if ($timestamp >= strtotime("-4 hours"))
        return "4 hours ago";
    if ($timestamp >= strtotime("-5 hours"))
        return "5 hours ago";
    if ($timestamp >= strtotime("-6 hours"))
        return "6 hours ago";
    if ($timestamp >= strtotime("-7 hours"))
        return "7 hours ago";
    if ($timestamp >= strtotime("-8 hours"))
        return "8 hours ago";
    if ($timestamp >= strtotime("-9 hours"))
        return "9 hours ago";
    if ($timestamp >= strtotime("-24 hours"))
        return "today";
    if ($timestamp >= strtotime("-1 days"))
        return "yesterday";
    if ($timestamp >= strtotime("-7 days"))
        return "on ".date("l",$timestamp);
    if ($timestamp >= strtotime("-1 week"))
        return "1 week ago";
    if ($timestamp >= strtotime("-2 week"))
        return "2 weeks ago";
    else
        return date("d.m.Y",$timestamp);
}

function get_time() {
    return (float)array_sum(explode(' ',microtime()));
}

function mysqli_get_array($query) {
    global $mysqli;
    $result = array();
    $ret = $mysqli->query($query);
    if (!$ret) {
        die($mysqli->error);
    }
    while ($row = $ret->fetch_assoc()) {
        $result[] = $row;
    }
    $ret->close();
    return $result;
}

function mysqli_get_value($query) {
    global $mysqli;
    $result = array();
    $ret = $mysqli->query($query);
    if (!$ret) {
        die($mysqli->error);
    }
    $result = $ret->fetch_row();
    $ret->close();
    return $result[0];
}

// obsolate
function render_array_files($mustache, $templates, $data) {
    $result = array();
    foreach ($templates as $template) {
        $tmpl = file_get_contents("templates/$template.template");
        $result[] = $mustache->render($tmpl, $data);
    }
    return $result;
}

$global = array(
    "root" => $config['root'],
    //"username" => "jcubic",
    "librarian" => true
);
if (isset($_SESSION['username'])) {
    $global['username'] = $_SESSION['username'];
}



$indent = 0;

class Template {
    function __construct($name, $data_privider) {
        global $indent;
        $this->name = $name;
        echo str_repeat(' ', $indent) . 'new ' . $this->name . "\n";
        $this->template = file_get_contents("templates/${name}.template");
        $this->get_data = $data_privider;
    }
    function render() {
        global $global, $indent;
        $indent++;
        $mustache = new Mustache_Engine(array(
            'escape' => function($val) { return $val; }
        ));
        if ($this->get_data === null) {
            echo str_repeat('  ', $indent) . $this->name  . " {no data}\n";
            return $mustache->render($this->template, $global);
        } else {
            $data = array();
            // can't execute closure directly in php :(
            $closure = $this->get_data;
            $ret = $closure();
            echo str_repeat('  ', $indent) . $this->name . " " . gettype($ret) .
                '[' . count($ret) . "]\n";
            
            foreach ($ret as $name => $value) {
                if ($this->name == 'most_popular_thumbs' && $name == 'content') {
                    echo '{' . gettype($value) . "}\n";
                }
                echo str_repeat(' ', $indent) . $this->name . " " . $name . "\n";
                if (gettype($value) == 'array') {
                    echo str_repeat('  ', $indent) .  "{array}\n";
                    $data[$name] = array();
                    $template = false;
                    foreach ($value as $k => $v) {
                        if (gettype($v) == 'object' &&
                            get_class($v) == 'Template') {
                            echo str_repeat('  ', $indent) . $k . " /template\n";
                            $data[$name][$k] = $v->render();
                            $template = true;
                        } else {
                            echo str_repeat('  ', $indent) . $k . " /val\n";
                            $data[$name][$k] = $v;
                        }
                    }
                    if ($template) {
                        echo str_repeat('  ', $indent) . $this->name .
                            " $name {implode}\n";
                        $data[$name] = implode("\n", $data[$name]);
                    }
                } else if (gettype($value) == 'object' &&
                           get_class($value) == 'Template') {
                    echo str_repeat('  ', $indent) . $this->name . " $name {template}\n";
                    $data[$name] = $value->render();
                    echo "string[" . strlen($data[$name]) . "]\n";
                    echo 'template[' . strlen($this->template) . "]\n";
                } else {
                    echo str_repeat('  ', $indent) . "{value}\n";
                    $data[$name] = $value;
                }
            }
            $indent--;
            if ($this->name == 'tag_cloud' && $name == 'tags') {
                echo $this->template;
                print_r($data);
            }
            return $mustache->render($this->template, array_merge($global, $data));
        }
    }
}

// calulate number from $min to 100 for $max, used for tag cloud
function size($min, $max) {
    return function($count) use($min, $max) {
        return round((((100-$min) * $count) / $max) + $min);
    };
}


$app->get('/', function() use ($app, $mysqli) {
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
    
    /*
    
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
    //(nsfw = 0 or nsfw=[nsfw])
    if (isset($common['nsfw']) && $common['nsfw']) {
        $nsfw = '(nsfw = 0 or nsfw = 1)';
    } else {
        $nsfw = 'nsfw = 0';
    }

    $query = "SELECT ocal_files.id, link, filename, upload_name, upload_date,".
        "full_path, file_num_download, count(DISTINCT ocal_favs.username) as ".
        "num_favorites, user_name FROM ocal_favs, ocal_files WHERE (nsfw = 0) " .
        "and upload_tags not like '%pd_issue%' and ocal_favs.clipart_id = ".
        "ocal_files.id AND ( YEAR(ocal_favs.fav_date) = YEAR(CURRENT_DATE)".
        "AND ( TO_DAYS(CURRENT_DATE) - TO_DAYS(ocal_favs.fav_date) < 8 ) )".
        "OR ( TO_DAYS(CURRENT_DATE) < 8 AND ( YEAR(ocal_favs.fav_date) = ".
        "(YEAR(CURRENT_DATE) - 1 ) ) AND TO_DAYS(ocal_favs.fav_date) > 355)".
        "GROUP BY ocal_files.id ORDER BY num_favorites DESC LIMIT 8";
    $ret = $mysqli->query($query);
    if (!$ret) {
        die($mysqli->error);
    }
    $clipart_list = array();
    while ($row = $ret->fetch_assoc()) {
        $filename_png = preg_replace("/.svg$/", ".png", $row['filename']);
        $human_date = human_date($row['upload_date']);
        $clipart_list[] = array_merge($row,
                                      array('filename_png' => $filename_png,
                                            'human_date' => $human_date,
                                            //TODO: check when close this query
                                            'have_fav' => false));
    }
    $ret->close();
    $clipart_list_template = file_get_contents("templates/clipart_list.template");
    $clipart_list = array_merge($common, array('cliparts' => $clipart_list));
    $data = array_merge($common, array(
        'content' => $mustache->render($clipart_list_template, $clipart_list)
    ));
    
    $mst_pop_tmpl = file_get_contents("templates/most_popular_thumbs.template");
    $content .= $mustache->render($mst_pop_tmpl, $data);
    
    
    $data = array_merge($common, array('sidebar' => $sidebar,
                                       'content' => $content));
    echo $mustache->render($main_template, $data);
    
    // load time
	$end_time = sprintf("%.4f", (get_time()-$start_time));
    echo "\n <!-- Time: $end_time seconds -->";
    */
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
