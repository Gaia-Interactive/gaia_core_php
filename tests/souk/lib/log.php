<?php

use Gaia\Test\Tap;
use Gaia\Store;

// how many tests are we gonna run?
Tap::plan( 66 );

function souk( $app, $user_id = NULL) {
    static $log;
    if( ! isset( $log ) ){
        $log = function($action, Gaia\Souk\Listing $listing ){
            Tap::debug( 'LOG: ' . $listing->id . ': ' . $action );
        };
    }
    return new Gaia\Souk\Logger( new Gaia\Souk( $app, $user_id ), $log);
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
