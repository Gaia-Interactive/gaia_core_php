<?php
namespace Gaia\Stockpile;
use Gaia\Test\Tap;

// how many tests are we gonna run?
Tap::plan(92);

// utility function for instantiating the object 
function stockpile( $app, $user_id ){
    return new Cacher( new Serial( $app, $user_id ), cachemock() );
}

$user_id = uniqueUserID();

// wrap in try/catch so we can fail and print out debug.
try {
    $large_number = stockpile($app, $user_id)->quantity( 100 );
    include __DIR__ . '/common_tests.php';
    include __DIR__ . '/cache_tests.php';
    include __DIR__ . '/serial_tests.php';
    include __DIR__ . '/trade_tests.php';

    
} catch( Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
