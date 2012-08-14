<?php

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

// calulate number from $min to 100 for $max, used for tag cloud
function size($min, $max) {
    return function($count) use($min, $max) {
        return round((((100-$min) * $count) / $max) + $min);
    };
}