<?php

require_once('mustache.php/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

$indent = 0;

define('TEMPLATE_DEBUG', false);

class Template {
    function __construct($name, $data_privider) {
        global $indent;
        $this->name = $name;
        if (TEMPLATE_DEBUG) {
            echo str_repeat(' ', $indent) . 'new ' . $this->name . "\n";
        }
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
            if (TEMPLATE_DEBUG) {
                echo str_repeat('  ', $indent) . $this->name .
                    " {no data}\n";
            }
            return $mustache->render($this->template, $global);
        } else {
            $data = array();
            // can't execute closure directly in php :(
            $closure = $this->get_data;
            $ret = $closure();
            if (TEMPLATE_DEBUG) {
                echo str_repeat('  ', $indent) . $this->name . " " . gettype($ret) .
                    '[' . count($ret) . "]\n";
            }
            foreach ($ret as $name => $value) {
                if (TEMPLATE_DEBUG) {
                    if ($this->name == 'most_popular_thumbs' &&
                        $name == 'content') {
                        echo '{' . gettype($value) . "}\n";
                    }
                    echo str_repeat(' ', $indent) . $this->name . " " .
                        $name . "\n";
                }
                if (gettype($value) == 'array') {
                    if (TEMPLATE_DEBUG) {
                        echo str_repeat('  ', $indent) .  "{array}\n";
                    }
                    $data[$name] = array();
                    $template = false;
                    foreach ($value as $k => $v) {
                        if (gettype($v) == 'object' &&
                            get_class($v) == 'Template') {
                            if (TEMPLATE_DEBUG) {
                                echo str_repeat('  ', $indent) . $k .
                                    " /template\n";
                            }
                            $data[$name][$k] = $v->render();
                            $template = true;
                        } else {
                            if (TEMPLATE_DEBUG) {
                                echo str_repeat('  ', $indent) . $k .
                                    " /val\n";
                            }
                            $data[$name][$k] = $v;
                        }
                    }
                    if ($template) {
                        if (TEMPLATE_DEBUG) {
                            echo str_repeat('  ', $indent) . $this->name .
                                " $name {implode}\n";
                        }
                        $data[$name] = implode("\n", $data[$name]);
                    }
                } else if (gettype($value) == 'object' &&
                           get_class($value) == 'Template') {
                    if (TEMPLATE_DEBUG) {
                        echo str_repeat('  ', $indent) . $this->name .
                            " $name {template}\n";
                    }
                    $data[$name] = $value->render();
                    if (TEMPLATE_DEBUG) {
                        echo "string[" . strlen($data[$name]) . "]\n";
                        echo 'template[' . strlen($this->template) . "]\n";
                    }
                } else {
                    if (TEMPLATE_DEBUG) {
                        echo str_repeat('  ', $indent) . "{value}\n";
                    }
                    $data[$name] = $value;
                }
            }
            $indent--;
            if (TEMPLATE_DEBUG) {
                if ($this->name == 'tag_cloud' && $name == 'tags') {
                    echo $this->template;
                    print_r($data);
                }
            }
            return $mustache->render($this->template, array_merge($global, $data));
        }
    }
}