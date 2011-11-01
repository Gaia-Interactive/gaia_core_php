#!/usr/bin/env php
<?php
// include default setup.
require __DIR__ . '/setup.php';

use Gaia\Test\Tap;


// how many tests are we gonna run?
Tap::plan( 66 );

function souk( $app, $user_id = NULL) {
    return new \Gaia\Souk( $app, $user_id );
}

// wrap in try/catch so we can fail and print out debug.
try {
    include __DIR__ . '/../lib/auction.test.php';
    include __DIR__ . '/../lib/transaction.test.php';
    include __DIR__ . '/../lib/search.test.php';
} catch( Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
