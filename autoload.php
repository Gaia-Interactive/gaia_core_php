<?php
spl_autoload_register(function($class) {
    $class = strtolower($class);
    if( substr( $class, 0, 5) != 'gaia\\' ) return;
    require  __DIR__.'/lib/'.strtr($class, '\\', '/').'.php';    
});
