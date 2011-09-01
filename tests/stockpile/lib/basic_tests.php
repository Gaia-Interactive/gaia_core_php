<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);


// start off by making sure we can add stuff to an account.    
$total = stockpile( $app, $user_id )->add( $item_id );
Tap::is( quantify( $total ), 1, 'add an item to the account');

// is the data there?
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 1, 'read the item we added');

// add multiple items
$total = stockpile( $app, $user_id )->add( $item_id, 2 );
Tap::is( quantify( $total ), 3, 'add multiple items to the account');

// make sure we can find those in the db.
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 3, 'read the items we added');

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
Transaction::commit();
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 4, 'add inside of a transaction and commit it. now we can see it!');

