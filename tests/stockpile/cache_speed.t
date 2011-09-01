#!/usr/bin/env php
<?php
namespace Gaia\Stockpile;
use Gaia\Test\Tap;
require __DIR__ . '/lib/setup.php';

// how many tests are we gonna run?
Tap::plan(5);

// utility function for instantiating the object 
function stockpile( $app, $user_id ){
    return new Cacher( new Tally( $app, $user_id), memcache() );
}

// wrap in try/catch so we can fail and print out debug.
try {
    include __DIR__ . '/lib/speed_tests.php';
    
} catch( Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
