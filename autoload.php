<?php

call_user_func( function(){
    // a utility function for downloading phar archives for vendor libraries.
    $download = function  ($url, $path) {
      $url_fp = fopen ($url, "rb");
      if (! $url_fp) throw new Exception('unable to download url: ' . $url );
      $path_fp = fopen ($path, "wb");
      if( ! $path_fp ) throw new Exception('unable to open path for writing: ' . $path);
      while(!feof($url_fp)) fwrite($path_fp, fread($url_fp, 1024 * 8 ), 1024 * 8 );
      fclose($url_fp);
      fclose($path_fp);
      
    };
    
    
    // do we want to auto-download the phar archives?
    if( ! file_exists( __DIR__ . '/DISABLE_AUTO_DOWNLOAD' ) ){
        // make sure they all exist and download if not.
        foreach( array('facebook', 'sfyaml', 'pheanstalk', 'predis') as $repo ){
            $path = __DIR__ . '/vendor/' . $repo . '.phar';
            if( file_exists( $path ) ) continue;
            $url = 'https://github.com/downloads/gaiaops/gaia_core_php/' . $repo . '.phar';
            $download( $url, $path );
        }
    }
});

// are we using the regular code or the phar archive of gaia_core_php?
if( file_exists( __DIR__ . '/ENABLE_PHAR' ) ){
    $gaia_path = 'phar://' . __DIR__ . '/bin/gaia_core_php.phar/';
} else {
    $gaia_path = __DIR__ . '/lib/';
}


// autoload all the vendor libs.
spl_autoload_register(function($classname) use( $gaia_path ){
    // force classname to lowercase ... make sure we are standardizing it.
    $class = strtolower($classname);
    
    // load the gaia namespaced classes.
    if( substr( $class, 0, 5) == 'gaia\\' ) {
            return include $gaia_path .strtr($class, '\\', '/').'.php';
    }
    
    // load facebook related classes.
    if( $class == 'facebook' ) {
        return @include  'phar://'. __DIR__ . '/vendor/facebook.phar/facebook.php';
    }
    if( $class == 'basefacebook' ) {
        return @include 'phar://'. __DIR__ . '/vendor/facebook.phar/base_facebook.php';
    }
    
    // load yaml vendor classes
    if( $class == 'sfyaml' ) {
        return @include 'phar://'. __DIR__ . '/vendor/sfyaml.phar/sfYaml.php';
    }
    
    // load pheanstalk
    if( $class == 'pheanstalk') {
        $base = 'phar://' . __DIR__ . '/vendor/pheanstalk.phar';
        include $base . '/Pheanstalk/ClassLoader.php';
        Pheanstalk_ClassLoader::register($base);
        include $base . '/Pheanstalk.php';
    }
    
    // load predis namespaced classes.
    if( substr($class, 0, 7) == 'predis\\' ) {
        return include 'phar://' . __DIR__.'/vendor/predis.phar/'.strtr($classname, '\\', '/').'.php';
    }
    // all done.
});







