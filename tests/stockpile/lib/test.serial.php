<?php
namespace Gaia\Stockpile;

use Gaia\Test\Tap;

// how many tests are we gonna run?
Tap::plan(90);

// utility function for instantiating the object 
function stockpile( $app, $user_id, $tran = NULL ){
    return new Serial( $app, $user_id, $tran );
}

// wrap in try/catch so we can fail and print out debug.
try {
    $large_number = stockpile($app, 1)->quantity( 100 );
    include __DIR__ . '/common_tests.php';
    include __DIR__ . '/transaction_extended_tests.php';
    include __DIR__ . '/serial_tests.php';
    include __DIR__ . '/trade_tests.php';

} catch( \Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
