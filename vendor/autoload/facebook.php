<?php
-// autoload facebook
spl_autoload_register(function($class) {
    $class = strtolower($class);
    if( $class == 'facebook' ) @include  __DIR__ . '/../facebook/php-sdk/src/facebook.php';
    if( $class == 'basefacebook' ) @include  __DIR__ . '/../facebook/php-sdk/src/base_facebook.php';
});
