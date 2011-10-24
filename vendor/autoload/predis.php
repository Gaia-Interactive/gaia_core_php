<?php

// predis autoloader.
spl_autoload_register(function($class) {
    if( substr( strtolower($class), 0, 7) != 'predis\\' ) return;
    @include  __DIR__.'/../predis/lib/'.strtr($class, '\\', '/').'.php';    
});

