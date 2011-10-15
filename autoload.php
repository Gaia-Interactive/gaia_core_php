<?php

// gaia's core auto-loader
spl_autoload_register(function($class) {
    $class = strtolower($class);
    if( substr( $class, 0, 5) != 'gaia\\' ) return;
    require  __DIR__.'/lib/'.strtr($class, '\\', '/').'.php';    
});

foreach( glob( __DIR__ . '/vendor/autoload/*.php') as $file ){
    if( substr(basename( $file ), 0, 1) == '.' ) continue;
    require $file;
}

