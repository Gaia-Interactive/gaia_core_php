<?php


// predis autoloader.
spl_autoload_register(function($class) {
    if( substr( strtolower($class), 0, 7) != 'predis\\' ) return;
    @include  __DIR__.'/predis/lib/'.strtr($class, '\\', '/').'.php';    
});

// autoload pheanstalk
@include __DIR__ .'/pheanstalk/pheanstalk_init.php';

// autoload facebook
spl_autoload_register(function($class) {
    $class = strtolower($class);
    if( $class == 'facebook' ) @include  __DIR__ . '/facebook/php-sdk/src/facebook.php';
    if( $class == 'basefacebook' ) @include  __DIR__ . '/facebook/php-sdk/src/base_facebook.php';
});




