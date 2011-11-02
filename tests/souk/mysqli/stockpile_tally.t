#!/usr/bin/env php
<?php
// include default setup.
require __DIR__ . '/setup.php';

use Gaia\Test\Tap;


// how many tests are we gonna run?
Tap::plan( 66 );


Gaia\Stockpile\Storage::attach( function (){return 'test';} );
Gaia\Stockpile\Storage::enableAutoSchema();


function souk( $app, $user_id = NULL) {
    return new Gaia\Souk\Stockpile( new \Gaia\Souk( $app, $user_id ), binder());
}


function binder(){
    return new Gaia\Souk\StockpileBinder('test1', 'test1', 1);
}
$binder = binder();

// wrap in try/catch so we can fail and print out debug.
try {
    //*
    $seller_id = uniqueUserId();
    $buyer_id = uniqueUserId();
    $item_id = uniqueNumber(1,1000000);
    $binder->itemAccount( $seller_id )->add( $item_id, 1000000);
    $binder->currencyAccount( $buyer_id )->add( $binder->currencyId(), 100000000 );
    include __DIR__ . '/../lib/auction.test.php';
    
    $seller_id = uniqueUserId();
    $buyer_id = uniqueUserId();
    $item_id = uniqueNumber(1,1000000);
    $binder->itemAccount( $seller_id )->add( $item_id, 1000000);
    $binder->currencyAccount( $buyer_id )->add( $binder->currencyId(), 100000000 );    
    
    include __DIR__ . '/../lib/transaction.test.php';
    
    $seller_id = uniqueUserId();
    $buyer_id = uniqueUserId();
    $item_id = uniqueNumber(1,1000000);
    $binder->itemAccount( $seller_id )->add( $item_id, 1000000);
    $binder->currencyAccount( $buyer_id )->add( $binder->currencyId(), 100000000 );
    
    include __DIR__ . '/../lib/search.test.php';
    
} catch( Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
