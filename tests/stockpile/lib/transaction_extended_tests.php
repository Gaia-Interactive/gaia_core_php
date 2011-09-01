<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();

// test transaction in/out of txn reads.
$item_id = uniqueNumber(1, 1000000);

Transaction::reset();
Transaction::claimStart();
stockpile( $app, $user_id )->add( $item_id, 10 );

// now we should see it.
Transaction::commit();
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify($total), 10, 'after it is committed we can still see the value we added');