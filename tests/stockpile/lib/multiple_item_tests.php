<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);

// add several items to the account.
$items = array( $item_id, uniqueNumber(1, 1000000), uniqueNumber(1, 1000000) );
sort( $items, SORT_NUMERIC );
Transaction::claimStart();
$stockpile = stockpile( $app, $user_id );
foreach( $items as $id ) $stockpile->add( $id );
Tap::ok( Transaction::commit(), 'add many items in a transaction');

// grab all the data at once
$all = $stockpile->all();
Tap::is( array_keys( $all), $items, 'got back all the items we put in');

// test multi-get
$some = $stockpile->get( $some_keys = array($items[0], $items[1]) );
Tap::is( array_keys( $some), $some_keys, 'multi-get only grabs the items we specified');

// test multi-get with an item that isn't in the list.
$stockpile = stockpile( $app, $user_id );
do {
    $not_in_list = uniqueNumber(1, 100000);
} while( in_array($not_in_list, $items ) );

$res = $stockpile->get( $not_in_list );
Tap::is( quantify($res), 0, 'get for an item that doesnt exist in user inventory returns zero');

$res = $stockpile->get( array( $not_in_list ) );
Tap::is( $res, array(), 'multi-get for an item that doesnt exist in user inventory returns empty array');

    
// search for partial
$stockpile->add( $item_id );
$some_in_list = array( $not_in_list, $item_id );
$res = $stockpile->get( $some_in_list );
Tap::is( array_keys( $res ), array($item_id), 'multi-get for partial match returns just the found results');

