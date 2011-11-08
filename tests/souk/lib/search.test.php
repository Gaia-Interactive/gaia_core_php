<?php
use Gaia\DB\Transaction;
use Gaia\Time;
use Gaia\Test\Tap;

if( ! isset( $seller_id ) ) $seller_id = uniqueUserId();
if( ! isset( $buyer_id ) ) $buyer_id = uniqueUserId();
if( ! isset( $item_id ) ) $item_id = uniqueNumber(1,100000);

Transaction::start();
$souk = souk( $app, $seller_id );
$listings = array();
for( $i = 1; $i<= 16; $i++){
    $listing = $souk->auction( array(($i % 2 == 0 ? 'bid' : 'price' )=>( ($i * 10 + 1) % 9 ), 'item_id'=>$item_id ) );
    $listings[ $listing->id ] = $listing;
    Time::offset(3600 * 12 + 91);

}
Transaction::commit();
$ids = souk( $app )->search( array('sort'=>'expires_soon', 'item_id'=>$item_id, 'seller'=>$seller_id) );

Tap::cmp_ok(count($ids), '>', 12, 'search expires_soon found results');

$found = TRUE;
foreach( $ids as $id ){
    if( isset( $listings[ $id ] ) ) continue;
    $found = FALSE;
    break;
}

Tap::ok( $found, 'returned only rows we created');


$owned = TRUE;
foreach( $ids as $id ){
    if( $listings[ $id ]->seller == $seller_id ) continue;
    $owned = FALSE;
    break;
}
Tap::ok( $owned, 'search returned only rows created by the seller');

$sorted_ids = $ids;
sort( $sorted_ids );

Tap::is( $ids, $sorted_ids, 'the ids are sorted in the correct order');

$res = $souk->fetch( $ids );

Tap::is( array_keys( $res ), $ids, 'fetch returns results sorted in the same order as the ids we passed in');


$ids = souk( $app )->search( array('sort'=>'just_added', 'item_id'=>$item_id, 'seller'=>$seller_id) );


Tap::cmp_ok(count($ids), '>', 12, 'search found results');

$found = TRUE;
foreach( $ids as $id ){
    if( isset( $listings[ $id ] ) ) continue;
    $found = FALSE;
    break;
}

Tap::ok( $found, 'returned only rows we created');


$owned = TRUE;
foreach( $ids as $id ){
    if( $listings[ $id ]->seller == $seller_id ) continue;
    $owned = FALSE;
    break;
}
Tap::ok( $owned, 'search returned only rows created by the seller');


$sorted_ids = $ids;

Tap::is( $ids, $sorted_ids, 'the ids are sorted in the correct order');


$sort_options = array(
        'expires_soon_delay',
    );

$res = array();
foreach( souk( $app )->fetch( souk( $app )->search( array('sort'=>'low_price', 'item_id'=>$item_id, 'seller'=>$seller_id) ) ) as $id => $listing ){
    $res[] = $listing->price;
}

$expected = $res;

sort($expected, SORT_NUMERIC);


Tap::is( print_r($res, TRUE), print_r($expected, TRUE), 'search sort by low_price returns results sorted by price from low to high');


$res = array();
foreach( souk( $app )->fetch( souk( $app )->search( array('sort'=>'high_price', 'item_id'=>$item_id, 'seller'=>$seller_id) ) ) as $id => $listing ){
    $res[] = $listing->price;
}

$expected = $res;

rsort($expected, SORT_NUMERIC);

Tap::is( print_r($res, TRUE), print_r($expected, TRUE), 'search sort by high_price returns results sorted by price from high to low');


$res = array();
foreach( souk( $app )->fetch( souk( $app )->search( array('sort'=>'expires_soon_delay', 'item_id'=>$item_id, 'seller'=>$seller_id) ) ) as $id => $listing ){
    $res[] = $listing->expires;
}


$expected = $res;

sort($expected, SORT_NUMERIC);

Tap::is( $res, $expected, 'search sort by expired_soon_delay sorted by expires');


Tap::cmp_ok( min($res), '>', Time::now() + 500, 'all of the results expire after now by more than 500 secs');


$res = array();
foreach( souk( $app )->fetch( souk( $app )->search( array( 'item_id'=>$item_id, 'seller'=>$seller_id, 'floor'=>4, 'ceiling'=>6) ) ) as $id => $listing ){
    $res[$listing->price] = TRUE;
}

$res = array_keys( $res );
sort( $res );

Tap::is( $res, array(4, 5, 6), 'setting floor and ceiling limits the result set to prices in the correct range');


$search = souk( $app )->search( array('item_id'=>$item_id, 'seller'=>$seller_id, 'closed'=>0) );
$id = array_shift( $search );

souk( $app )->close( $id );

$ids = souk( $app )->search( array('item_id'=>$item_id, 'seller'=>$seller_id, 'closed'=>0) );

Tap::ok(! in_array( $id, $ids, TRUE), 'after closing an item, it no longer appears in the list of unclosed items');


$res = souk( $app )->fetch( souk( $app )->search( array('item_id'=>$item_id, 'seller'=>$seller_id, 'closed'=>0, 'only'=>'bid') ) );


$bidonly = TRUE;
foreach( $res as $listing ){
    if( ! $listing->step || $listing->price ) $bidonly = FALSE;
}

Tap::ok($bidonly, 'searching with only=>bid param returns results that are all bid only items, no prices');


$res = souk( $app )->fetch( souk( $app )->search( array('item_id'=>$item_id, 'seller'=>$seller_id, 'closed'=>0, 'only'=>'buy') ) );


$buynow = TRUE;
foreach( $res as $listing ){
    if( $listing->step || ! $listing->price ) $buynow = FALSE;
}

Tap::ok($buynow, 'searching with only=>buy param returns results that are all buy now only items, no bids');


unset ($seller_id );
unset( $buyer_id );
unset( $item_id );
