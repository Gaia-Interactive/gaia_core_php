<?php

ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


// create a function for automagically loading all the dependent classes.
spl_autoload_register(function($class) {
    $class = strtolower($class);
    $file =  __DIR__.'/../lib/'.strtr($class, '\\', '/').'.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
});
