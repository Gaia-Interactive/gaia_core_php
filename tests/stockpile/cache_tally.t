#!/usr/bin/env php
<?php
namespace Gaia\Stockpile;
use Gaia\Test\Tap;
require __DIR__ . '/lib/setup.php';


// how many tests are we gonna run?
Tap::plan(74);

// utility function for instantiating the object 
function stockpile( $app, $user_id ){
    $cacher = new \Gaia\Cache\Memcache;
    $cacher->addServer('127.0.0.1', '11211');
    return new Cacher( new Tally( $app, $user_id ), $cacher );
}

// wrap in try/catch so we can fail and print out debug.
try {
    include __DIR__ . '/lib/common_tests.php';
    include __DIR__ . '/lib/cache_tests.php';
    include __DIR__ . '/lib/trade_tests.php';

} catch( Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
