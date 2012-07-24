<?php

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
        $tmpl = file_get_contents("templates/$template.template");
        $result[] = $mustache->render($tmpl, $data);
    }
    return $result;
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

$app->get('/', function() use ($app, $mysqli) {
    $start_time = get_time();
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
    if (!class_exists($name)) {
        if (file_exists($filename)) {
            require_once($filename);
            handle_json_rpc(new $name());
        } else {
            $msg = "ERROR: service `$name' not found";
            return json_encode(array(
                "error" => array("code" => 108, "message" => $msg)
            ));
        }
    } else {
        require('libs/json-rpc/json-rpc.php');
        handle_json_rpc(new $name());
    }
});

$app->run();

?>
