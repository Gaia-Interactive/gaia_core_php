<?php
namespace Gaia\Stockpile;
use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();
$other_id = uniqueUserId();
$item_id = uniqueNumber(1, 1000000);

Transaction::claimStart();
$core = stockpile( $app, $user_id );
$other = stockpile( $app, $other_id );
$trade = new Transfer( $core, $other);

Tap::is( quantify( $starting_total = $core->add($item_id, 3) ), 3, 'starting with 3 items');
Tap::is(quantify( $v = $trade->subtract( $item_id, 1 )), 2, 'trade subtract - says we have 2 items left');
//print_r( $v );

Tap::is( quantify($v = $core->get( $item_id )), 2, 'after subtracting, verified we have 2 items left' );
//print_r( $v );
Tap::is( quantify($other->get( $item_id )), 1, 'other party now has 1');

Tap::is( quantify($trade->add($item_id, 1)), 3, 'now add the item back in the trade. my count goes back up to 3');
Tap::is( quantify($other->get( $item_id )), 0, 'other party now has nothing');

Tap::is( $trade->get( $item_id ), $core->get( $item_id ), 'core and trade return same result');
Transaction::commit();
    
// nested trade?
$e = NULL;
try { new Transfer( $trade, $other ); } catch( Exception $e ){ }
Tap::ok( $e instanceof Exception && preg_match('/transfer/i', $e->getMessage() ), 'no nesting of transfers');

$e = NULL;
Transaction::claimStart();
try { new Transfer( $core, $core ); } catch( Exception $e ){ }
Tap::ok( $e instanceof Exception && preg_match('/need\stwo/i', $e->getMessage() ), 'enforce two different parties to trade');

$e = NULL;
Transaction::reset();
try { new Transfer( stockpile( $app, $other_id), stockpile( $app, $other_id ) ); } catch( Exception $e ){ }
Tap::ok( $e instanceof Exception && preg_match('/transaction/i', $e->getMessage() ), 'blow up when no transaction');

$e = NULL;
try { new Transfer( stockpile( $app, $other_id ), stockpile( $app, $other_id ) ); } catch( Exception $e ){ }
Tap::ok( $e instanceof Exception && preg_match('/transaction/i', $e->getMessage() ), 'blow up when neither has a transaction');

Transaction::reset();
Transaction::claimStart();
$trade = new Transfer( stockpile($app, $user_id), stockpile('test2', $user_id) );
Tap::is( quantify($trade->subtract($item_id)), 2, 'move items for same user to different app (escrow example with transaction)');

Tap::is( quantify($trade->add( $item_id )), 3, 'add it back from other app to main');

Tap::ok( Transaction::commit(), 'transaction commits successfully');

