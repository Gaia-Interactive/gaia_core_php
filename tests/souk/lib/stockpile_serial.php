<?php

use Gaia\Test\Tap;


// how many tests are we gonna run?
Tap::plan( 66 );


Gaia\Stockpile\Storage::attach( function (){return 'test';} );
Gaia\Stockpile\Storage::enableAutoSchema();


function souk( $app, $user_id = NULL) {
    return new Gaia\Souk\Stockpile( new \Gaia\Souk( $app, $user_id ), binder());
}


class Souk_StockpileSerialBinderTest1 implements Gaia\Souk\Stockpilebinder_Iface {
    protected static $item_app = 'test1';
    protected static $currency_app = 'test1';
    protected static $currency_id = 1;
    
    public function itemAccount( $user_id){
        return  new Gaia\Stockpile\Serial( self::$item_app, $user_id );
    }
    
    public function currencyAccount( $user_id ){
        return new Gaia\Stockpile\Tally( self::$currency_app, $user_id );
    }
    
    public function currencyId(){
        return self::$currency_id;
    }
}

function binder(){
    return new Souk_StockpileSerialbinderTest1();
}

$binder = binder();

// wrap in try/catch so we can fail and print out debug.
try {
    //*
    $seller_id = uniqueUserId();
    $buyer_id = uniqueUserId();
    $item_id = uniqueNumber(1,1000000);
    $binder->itemAccount( $seller_id )->add( $item_id, 100);
    $binder->currencyAccount( $buyer_id )->add( $binder->currencyId(), 100000000 );
    include __DIR__ . '/auction.test.php';
    
    $seller_id = uniqueUserId();
    $buyer_id = uniqueUserId();
    $item_id = uniqueNumber(1,1000000);
    $binder->itemAccount( $seller_id )->add( $item_id, 100);
    $binder->currencyAccount( $buyer_id )->add( $binder->currencyId(), 100000000 );    
    
    include __DIR__ . '/transaction.test.php';
    
    $seller_id = uniqueUserId();
    $buyer_id = uniqueUserId();
    $item_id = uniqueNumber(1,1000000);
    $binder->itemAccount( $seller_id )->add( $item_id, 100);
    $binder->currencyAccount( $buyer_id )->add( $binder->currencyId(), 100000000 );
    
    include __DIR__ . '/search.test.php';
    
} catch( Exception $e ){
    Tap::fail( 'unexpected exception thrown' );
    print $e;
}
