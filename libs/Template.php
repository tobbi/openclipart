<?php

require_once('mustache.php/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

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