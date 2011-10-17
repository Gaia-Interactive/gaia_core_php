<?php

namespace Gaia\Stockpile;

use \Gaia\DB\Transaction;
use \Gaia\Test\Tap;

$user_id = uniqueUserID();

// start off by making sure we can add stuff to an account.
$stockpile = stockpile( $app, $user_id );
$start = microtime(TRUE);
for( $i = 0; $i < 1000; $i++) $stockpile->add( $i + 1, uniqueNumber(1,10000));
$elapsed = microtime(TRUE) - $start;
Tap::ok( $elapsed < 10, "added $i items in 10 seconds or less (no transaction). did it in: " . number_format( $elapsed, 3) . ' seconds' );

// read back those same items
$start = microtime(TRUE);
for( $i = 0; $i < 1000; $i++) $stockpile->get( $i + 1);
$elapsed = microtime(TRUE) - $start;
Tap::ok( $elapsed < 10, "read $i items in 10 seconds or less (serial order). did it in: " . number_format( $elapsed, 3) . ' seconds' );

// change user ids and do it again, this time with a transaction.
$user_id = uniqueUserID();
Transaction::claimStart();
$stockpile = stockpile( $app, $user_id);
$start = microtime(TRUE);
for( $i = 0; $i < 1000; $i++) $stockpile->add( $i + 1, uniqueNumber(1,10000));
Transaction::commit();
$elapsed = microtime(TRUE) - $start;
Tap::ok( $elapsed < 10, "added $i items in 10 seconds or less (with transaction). did it in: " . number_format( $elapsed, 3) . ' seconds' );

$stockpile = stockpile( $app, $user_id );
$start = microtime(TRUE);
for( $i = 0; $i < 1000; $i+=100) $stockpile->get( range($i + 1, $i+101) );
$elapsed = microtime(TRUE) - $start;
Tap::ok( $elapsed < 3, "read $i items in 3 seconds or less (multi-get, chunks of 100). did it in: " . number_format( $elapsed, 3) . ' seconds' );

$start = microtime(TRUE);
$stockpile->all();
$elapsed = microtime(TRUE) - $start;
Tap::ok( $elapsed < 3, "read $i items in 3 seconds or less (all at once). did it in: " . number_format( $elapsed, 3) . ' seconds' );