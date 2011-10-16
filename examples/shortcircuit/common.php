<?php
//use Gaia\Test\Tap;

include __DIR__ . '/../common.php';

spl_autoload_register(function($class) {
    $class = strtolower($class);
    $file =  __DIR__.'/lib/'.strtr($class, '\\', '/').'.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
});
