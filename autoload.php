<?php

// load from phar if available.
$lib_dir = __DIR__.'/lib/';
$pharfile = __DIR__ . '/gaia_core_php.phar';
if( extension_loaded('phar') ) $pharfile .= '.tar.gz';
if( file_exists( $pharfile) ) $lib_dir = "phar://$pharfile/";


// autoload all the vendor libs.
spl_autoload_register(function($class) use( $lib_dir ) {
    // force classname to lowercase ... make sure we are standardizing it.
    $class = strtolower($class);
    
    // load facebook related classes.
    if( $class == 'facebook' ) 
        return @include  __DIR__ . '/vendor/facebook/php-sdk/src/facebook.php';
    if( $class == 'basefacebook' ) 
        return @include  __DIR__ . '/vendor/facebook/php-sdk/src/base_facebook.php';
    
    // load yaml vendor classes
    if( $class == 'sfyaml' ) 
        return @include  __DIR__ . '/vendor/yaml/lib/sfYaml.php';
    
    // check to see if the base pheanstalk object is loaded and include it if not.
    // at the same time set up the Pheanstalk_ClassLoader for all the sub classes.
    // don't want to have to do pheanstalk autoload hook until we need to since it is 
    // a little expensive to do on every page load.
    if( $class == 'pheanstalk') {
        $class_dir = __DIR__ . '/vendor/pheanstalk/classes';
        include $class_dir . '/Pheanstalk/ClassLoader.php';
        Pheanstalk_ClassLoader::register($class_dir);
        include $class_dir . '/Pheanstalk.php';
    }
    
    // load the gaia namespaced classes.
    if( substr( $class, 0, 5) == 'gaia\\' ) 
        return include  $lib_dir .strtr($class, '\\', '/').'.php';   
        
    // load predis namespaced classes.
    if( substr($class, 0, 7) == 'predis\\' ) 
        return @include  __DIR__.'/vendor/predis/lib/'.strtr($class, '\\', '/').'.php';
    
    // all done.
});

