<?php

// autoload all the vendor libs.
spl_autoload_register(function($classname) {
    // force classname to lowercase ... make sure we are standardizing it.
    $class = strtolower($classname);
    
    // load facebook related classes.
    if( $class == 'facebook' ) {
        return @include  __DIR__ . '/vendor/facebook-php-sdk/src/facebook.php';
    }
    if( $class == 'basefacebook' ) {
        return @include  __DIR__ . '/vendor/facebook-php-sdk/src/base_facebook.php';
    }
    
    // load yaml vendor classes
    if( $class == 'sfyaml' ) {
        return @include __DIR__ . '/vendor/yaml/lib/sfYaml.php';
    }
    
    // check to see if the base pheanstalk object is loaded and include it if not.
    // at the same time set up the Pheanstalk_ClassLoader for all the sub classes.
    // don't want to have to do pheanstalk autoload hook until we need to since it is 
    // a little expensive to do on every page load.
    if( $class == 'pheanstalk') {
        $base = __DIR__ . '/vendor/pheanstalk/classes';
        include $base . '/Pheanstalk/ClassLoader.php';
        Pheanstalk_ClassLoader::register($base);
        include $base . '/Pheanstalk.php';
    }
    
    // load the gaia namespaced classes.
    if( substr( $class, 0, 5) == 'gaia\\' ) 
        return include  __DIR__ . '/lib/' .strtr($class, '\\', '/').'.php';   
        
    // load predis namespaced classes.
    if( substr($class, 0, 7) == 'predis\\' ) {
        return include __DIR__ . '/vendor/predis/lib/'.strtr($classname, '\\', '/').'.php';
    }
    // all done.
});

