<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();

// test with a big item id number.
$big_item_id = bcsub(bcpow(2, 32), 1);
stockpile( $app, $user_id )->add( $big_item_id );
$total = stockpile( $app, $user_id )->get( $big_item_id );
Tap::is( quantify($total), 1, 'big item id works just fine');
