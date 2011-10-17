<?php
// test transaction in/out of txn reads.
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);

Transaction::reset();
Transaction::claimStart();
stockpile( $app, $user_id )->add( $item_id, 10 );

// read the value outside of the transaction ... shouldn't be able to see it yet.
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 10, 'get outside of txn sees the value we added - it is in the cache optimistically');

// now it should go away
Transaction::rollback();
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total), 0, 'after it is rolled back the value disappears from the cache');


Transaction::claimStart();
stockpile( $app, $user_id )->add( $item_id, 10 );
$stockpike = stockpile( $app, $user_id );
$stockpile->forceRefresh( TRUE );
$total = $stockpile->get( $item_id );

Tap::is( quantify($total), 0, 'when force-refreshing the cache, item disappears that we added but didnt commit');

Transaction::rollback();
