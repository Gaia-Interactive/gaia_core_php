<?php
namespace Gaia\Stockpile;
use Gaia\Test\Tap;

include __DIR__ . '/../../assert/memcache_installed.php';
include __DIR__ . '/../../assert/memcache_running.php';


// how many tests are we gonna run?
Tap::plan(5);

// utility function for instantiating the object 
function stockpile( $app, $user_id ){
    return new Cacher( new Tally( $app, $user_id), memcache() );
}

// wrap in try/catch so we can fail and print out debug.
try {
    include __DIR__ . '/speed_tests.php';
    
} catch( \Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
