<?php
namespace Gaia\Stockpile;
use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);


// test transaction support.
Transaction::claimStart();
$total = stockpile( $app, $user_id )->add( $item_id );
Tap::is( quantify( $total ), 4, 'add inside a transaction');

// revert the transaction
Transaction::rollback();
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 3, 'after txn rollback, the value we added isnt there');

// add inside a transaction and commit it.
Transaction::claimStart();
$total = stockpile( $app, $user_id )->add( $item_id );
Tranaction::commit();
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 4, 'add inside of a transaction and commit it. now we can see it!');
