<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$item_id = uniqueNumber(1, 1000000);

$total = stockpile( $app, $user_id )->add( $item_id, 4 );



// add a large amount
if( ! isset( $large_number ) ) $large_number = bcsub(bcpow(2, 62), 1);
$previous_total = $total;
$total = stockpile( $app, $user_id )->add( $item_id, $large_number );
Tap::is( quantify( $total ), bcadd(quantify($large_number), quantify( $previous_total )), 'add a large quantity');

// read it.
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), bcadd(quantify($large_number), quantify( $previous_total )), 'read back the large quantity we added');

// subtract it.
$total = stockpile( $app, $user_id )->subtract( $item_id, $large_number );
Tap::is( quantify( $total ), quantify( $previous_total ), 'subtract a large quantity');

// back where we started
$total = stockpile( $app, $user_id )->get( $item_id );
Tap::is( quantify( $total ), 4, 'after subtract, amount is correct');

// remove the item completely
Tap::is( quantify( stockpile( $app, $user_id )->subtract( $item_id, $total ) ), 0, 'remove everything');
Tap::is( quantify(stockpile( $app, $user_id )->get( $item_id )), 0, 'get returns 0');
Tap::is( stockpile( $app, $user_id )->get( array( $item_id ) ), array(), 'multi-get returns empty array - deleted item not in there.');

Tap::is( quantify(stockpile( $app, $user_id )->add( $item_id, $total ) ), quantify( $total ), 'add it back, as we were');