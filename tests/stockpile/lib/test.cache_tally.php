<?php
namespace Gaia\Stockpile;
use Gaia\Test\Tap;

// how many tests are we gonna run?
Tap::plan(74);

// utility function for instantiating the object 
function stockpile( $app, $user_id ){
    return new Cacher( new Tally( $app, $user_id ), cachemock() );
}

// wrap in try/catch so we can fail and print out debug.
try {
    include __DIR__ . '/common_tests.php';
    include __DIR__ . '/cache_tests.php';
    include __DIR__ . '/trade_tests.php';

} catch( \Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
