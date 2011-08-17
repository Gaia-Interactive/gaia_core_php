<?php

// create a function for automagically loading all the dependent classes.
spl_autoload_register(function($class) {
    $class = strtolower($class);
    $file =  __DIR__.'/../lib/'.strtr($class, '\\', '/').'.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
});
