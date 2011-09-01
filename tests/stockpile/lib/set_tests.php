<?php
namespace Gaia\Stockpile;
use \Gaia\Test\Tap;
use \Gaia\DB\Transaction;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);


// start off by making sure we can add stuff to an account.    
$total = stockpile( $app, $user_id )->set( $item_id, 1 );
Tap::is( quantify( $total ), 1, 'set an item to the account');

// is the data there?
$total = stockpile( $app, $user_id )->get( $item_id, 1 );
Tap::is( quantify( $total ), 1, 'read the item we added');

// add multiple items
$total = stockpile( $app, $user_id )->set( $item_id, 2 );
Tap::is( quantify( $total ), 2, 'set multiple items to the account');

// make sure we can find those in the db.
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 2, 'read the items we set');

// test transaction support.
$start = Transaction::claimStart();
$total = stockpile( $app, $user_id )->set( $item_id, 1 );
Tap::is( quantify( $total ), 1, 'set inside a transaction');

// revert the transaction
Transaction::rollback();
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 2, 'after txn rollback, the value we set is reverted');

// add inside a transaction and commit it.
Transaction::claimStart();
$total = stockpile( $app, $user_id )->set( $item_id, 1 );
Transaction::commit();
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 1, 'set inside of a transaction and commit it. now we can see it!');

