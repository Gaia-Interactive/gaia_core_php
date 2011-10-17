<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);

stockpile( $app, $user_id )->add( $item_id, 5);

// cant subtract zero
$e = NULL;
try {
    stockpile( $app, $user_id )->subtract( $item_id, 0);
} catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/cannot subtract/', $e->getMessage()), 'cant subtract zero');

// subtract more than we have.
$starting_total = stockpile( $app, $user_id )->get( $item_id );
$e = NULL;
try {
    $stockpile = stockpile( $app, $user_id );
    $stockpile->subtract( $item_id, quantify( $stockpile->get( $item_id ) ) + 1 );
} catch( \Exception $e ){ }
Tap::ok( $e instanceof \Exception && preg_match('/not enough/', $e->getMessage()), 'wont allow to subtract more than you have');

// after failed subtraction, amount is still correct.
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( $total, $starting_total, 'after failed subtract, amount is still the same');
