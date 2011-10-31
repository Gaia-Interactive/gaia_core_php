<?php
namespace Gaia\Stockpile;
use \Gaia\Test\Tap;
use \Gaia\DB\Transaction;

function sanitizeQuantity( Quantity $q ){
    if( ! $q instanceof Quantity ) return;
    foreach( $q->all() as $serial => $properties ){
        if( isset( $properties['xp'] ) ) continue;
        $properties['xp'] = '1';
        $q->set( $serial, $properties );
    }
}



$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 10000000);

$quantity = stockpile( $app, $user_id )->add($item_id);
$serials = $quantity->serials();
$first_serial = $serials[0];
Tap::ok( is_array( $serials ) && count( $serials ) == 1, 'after adding a new item, quantity returns 1 serial');
Tap::ok( ctype_digit( quantify( $serials[0] ) ), 'serial returned is an digit');
Tap::ok( $first_serial > 0, 'serial is greater than 1');
advanceCurrentTime(1);
$quantity = stockpile( $app, $user_id )->add($item_id, 3);
$serials = $quantity->serials();
Tap::ok( is_array( $serials ) && count( $serials ) == 4, 'after adding 3 new items, quantity returns 4 serials, since we had 1 before');
$digit_test = TRUE;
foreach( $serials as $serial ){
    if( ! ctype_digit( quantify( $serial ) ) ) $digit_test = FALSE;
    if( $serial < 1 ) $digit_test = FALSE;
}
Tap::ok( $digit_test, 'all the serials returned are integers');

$quantity = stockpile( $app, $user_id )->subtract($item_id, 3);

Tap::is( $quantity->serials(), array( $first_serial ), 'after deleting 3, back down to my original item');

$quantity->set( $serial = lastElement( $quantity->serials() ), array('event'=>'xmas'));
$res = stockpile( $app, $user_id )->add( $item_id, $quantity );
Tap::is( $quantity->all(), $res->all(), 'after adding properties, returned result matches what we just stored');
Tap::is( $res->get( $serial ), array('event'=>'xmas'), 'the property was properly stored for the serial');

for( $i =0; $i < 10; $i++) stockpile( $app, $user_id )->add( $item_id, $quantity );

Tap::is( stockpile( $app, $user_id)->get( $item_id ), $quantity, 'after adding the same serial over and over, still only have the one item');

// need to think about how to test deadlocks ... without deadlocking in a test :P

$user_id = uniqueUserID();

$stockpile = stockpile($app, $user_id);
$item_id = uniqueNumber(1, 10000000);
$quantity = $stockpile->quantity(1);
$serial = lastElement( $quantity->serials() );
$quantity->set( $serial, $props = range(1,1200) );
$res = stockpile($app, $user_id)->add( $item_id, $quantity );
Tap::is( $res, $quantity, 'after storing a large list of properties, result coming back still matches. db didn\'t truncate');

// this test doesn't quite work because of caching layer.
$item_id = uniqueNumber(1, 10000000);
$stockpile = stockpile($app, $user_id);
$stockpile->add($item_id);
$q = $stockpile->get( $item_id );
Tap::is( lastElement($q->all()), array(), 'empty properties before callback validator is attached');

$stockpile = new QuantityInspector($stockpile , 'Gaia\Stockpile\sanitizeQuantity');
$q = $stockpile->get( $item_id );
Tap::is( lastElement($q->all()), array('xp'=>1), 'callback function populated xp into read quantity object');

$stockpile = new QuantityInspector( stockpile($app, $user_id), 'Gaia\Stockpile\sanitizeQuantity');
$item_id = uniqueNumber(1, 10000000);

$res = $stockpile->add($item_id, 1);
Tap::is( lastElement($res->all()), array('xp'=>1), 'callback function populated xp into the quantity on add');



$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 10000000);

$stockpile = stockpile($app, $user_id);
$total = $stockpile->add( $item_id, 3 );
Tap::is( $stockpile->set( $item_id, $total ), $total, 'When calling set with the same set of serials, no items created or subtracted');
$new = $total->grab( array( lastElement( $total->serials() )  ) );
Tap::is( $stockpile->set( $item_id, $new ), $new, 'When removing  a serial, everything matches up');

Tap::is( $stockpile->set( $item_id, $total ), $total, 'When adding back a serial, everything matches up');




$user_id1 = uniqueUserID();
$user_id2 = uniqueUserID();
$item_id = uniqueNumber(1, 10000000);

Transaction::reset();
Transaction::claimStart();

$stockpile1 = stockpile($app, $user_id1);
$stockpile2 = stockpile($app, $user_id2);

$start = microtime(TRUE);
$total1 = $stockpile1->add($item_id);
$total2 = $stockpile2->add($item_id);
Transaction::commit();
$elapsed = microtime(TRUE) - $start;

Tap::cmp_ok($elapsed, '<', 1, 'two users adding the same item doesnt create deadlock. took ' . number_format($elapsed, 2) . ' seconds');
Tap::isnt($total1, $total2, 'the items created for these two users are different');




