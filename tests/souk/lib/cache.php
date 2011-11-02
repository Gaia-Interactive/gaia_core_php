<?php

use Gaia\Test\Tap;
use Gaia\Store;

// how many tests are we gonna run?
Tap::plan( $expected_test_count );

function souk( $app, $user_id = NULL) {
    return new Gaia\Souk\Cacher( new Gaia\Souk( $app, $user_id ), cachemock() );
}
// wrap in try/catch so we can fail and print out debug.
try {
    include __DIR__ . '/auction.test.php';
    include __DIR__ . '/transaction.test.php';
    include __DIR__ . '/search.test.php';
} catch( Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
