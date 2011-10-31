<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

function convertResultToScalar( array $res ){
    $ret = array();
    foreach( $res as $item_id => $quantity ){
        $ret[ $item_id ] = quantify( $quantity );
    }
    return $ret;
}

$user_id = uniqueUserId();
$sorter = new CompareSort( $sorted = array( 12 => 3, 13 => 2, 14 => 1 ) );

$list = array_fill_keys(range(9, 15 ), 1);
uksort( $list, array( $sorter, 'compare') );

$expected = array(12=>1, 13=>1, 14=>1, 15=>1, 11=>1, 10=>1, 9=>1);
Tap::is( $list, $expected, 'sorts based on the ordering we specify, then in descending order');

$stockpile = new Sorter( stockpile( $app, $user_id ) );
foreach( range(9, 15) as $item_id ){
    $stockpile->add( $item_id );
}
$stockpile->sort( array( 12, 13, 14 ) );

Tap::is( convertResultToScalar( $stockpile->all() ), $expected, 'custom sorter works properly with real data');


$user_id = uniqueUserId();
$stockpile = new RecentSorter( stockpile( $app, $user_id ) );
foreach( range(13, 15) as $item_id ){
    $stockpile->add( $item_id );
    advanceCurrentTime(1);
}

Tap::is( convertResultToScalar( $stockpile->all() ), array_fill_keys( range(15, 13), 1), 'recentsorter sorts keys by time in reverse order');

$stockpile->add(13);

Tap::is( convertResultToScalar( $stockpile->all() ), array(13=>2, 15=>1, 14=>1), 'when adding to an existing item that one pops to the top');

$stockpile->sort(array(14,15 ) );

Tap::is( convertResultToScalar( $stockpile->all() ), array(14=>1, 15=>1, 13=>2 ), 'custom sorting layers over the top');


$user_id = uniqueUserId();
$stockpile = new FirstAddedSorter( stockpile( $app, $user_id ) );
foreach( range(13, 15) as $item_id ){
    $stockpile->add( $item_id );
    advanceCurrentTime(1);
}
Tap::is( convertResultToScalar( $stockpile->all() ), array_fill_keys( range(15, 13), 1), 'firstaddedsorter sorts keys by time in reverse order when first adding');

$stockpile->add(13);

Tap::is( convertResultToScalar( $stockpile->all() ), array(15=>1, 14=>1, 13=>2), 'when adding to an existing item that one stays where it is');

$stockpile->sort(array(14,15 ) );

Tap::is( convertResultToScalar( $stockpile->all() ), array(14=>1, 15=>1, 13=>2 ), 'custom sorting layers over the top');


$user_id = uniqueUserId();
$stockpile = new OldestSorter( stockpile( $app, $user_id ) );
foreach( range(15, 13) as $item_id ){
    $stockpile->add( $item_id );
    advanceCurrentTime(1);
}
Tap::is( convertResultToScalar( $stockpile->all() ), array(15=>1, 14=>1, 13=>1), 'oldestsorter sorts keys by time in order of add');

$stockpile->add(13);

Tap::is( convertResultToScalar( $stockpile->all() ),array(15=>1, 14=>1, 13=>2), 'when adding to an existing item that one stays where it is');

$stockpile->sort(array(14,15 ) );

Tap::is( convertResultToScalar( $stockpile->all() ), array(14=>1, 15=>1, 13=>2 ), 'custom sorting layers over the top');

$stockpile->sort(array(13,15 ) );

Tap::is( convertResultToScalar( $stockpile->all() ), array(13=>2, 15=>1, 14=>1 ), 'adding a different layering of custom filtering over the top');

$stockpile->subtract(15);
$stockpile->add(15);

Tap::is( convertResultToScalar( $stockpile->all() ), array(13=>2, 14=>1, 15=>1 ), 'after deleting an item and adding it again, it shows up at the end of the list');
