<?php
namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;
/*
$user_id = uniqueUserId();
$i1 = uniqueNumber(1, 10000000);
$i2 = uniqueNumber(1, 10000000);

$t1 = txn();
$s1 = stockpile( $app, $user_id );
$t2 = txn();
$s2 = stockpile( $app, $user_id );

$r1 = $s1->add($i1);
$t1->commit();

$r1 = $s1->subtract($i1);
$r2 = $s2->add( $i2 );



$t1->commit();
$t2->rollback();

Tap::is($s1->all(), array(), 'After adding and subtracting one item, then adding an rolling back another, we have no items left');



$i1 = uniqueNumber(1, 10000000);
$i2 = uniqueNumber(1, 10000000);

$t1 = txn();
$s1 = stockpile( $app, $user_id, $t1 );
$t2 = txn();
$s2 = stockpile( $app, $user_id, $t2 );

$r1 = $s1->add($i1);
$t1->commit();

$r1 = $s1->subtract($i1);
$r2 = $s2->add( $i2 );



$t1->commit();
$t2->commit();


Tap::is($s1->all(), array( $i2=>$r2 ), 'After adding and subtracting one item, then adding and committing another, we have 1 item');

*/