#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;
use Gaia\DB\Transaction;

include __DIR__ . '/../common.php';
Tap::plan(14);

$commit = $rollback = 0;
$commit_handler = function( $var = 0 ) use ( & $commit ){ $commit+= $var;};
$rollback_handler = function($var = 0) use ( & $rollback ){$rollback+=$var;};


Tap::is(Transaction::start(), TRUE, 'started a transaction');
Tap::ok( Transaction::inProgress(), 'transaction in progress');

Transaction::onCommit( $commit_handler, array(5) );
Transaction::onCommit( $commit_handler, array(5) );
Transaction::onCommit( $commit_handler, array(1) );
Transaction::onRollback( $rollback_handler, array(5) );
Transaction::onRollback( $rollback_handler, array(5) );
Transaction::onRollback( $rollback_handler, array(1) );

Tap::is( Transaction::commit(), TRUE, 'commited the transaction');

Tap::is($commit, 6, 'commit handler triggered with correct params');
Tap::is($rollback, 0, 'rollback handler not touched');

$commit = 0;
$rollback = 0;

Tap::ok( ! Transaction::inProgress(), 'transaction no longer in progress');

Tap::is(Transaction::start(), TRUE, 'started a new transaction');
Tap::is( Transaction::commit(), TRUE, 'commited the transaction');

Tap::is($commit, 0, 'commit handler not triggered, not attached');
Tap::is($rollback, 0, 'rollback handler not touched');


$commit = 0;
$rollback = 0;


Tap::is(Transaction::start(), TRUE, 'started a new transaction');
Transaction::onCommit( $commit_handler, array(5) );
Transaction::onCommit( $commit_handler, array(5) );
Transaction::onCommit( $commit_handler, array(1) );
Transaction::onRollback( $rollback_handler, array(5) );
Transaction::onRollback( $rollback_handler, array(5) );
Transaction::onRollback( $rollback_handler, array(1) );
Tap::is( Transaction::rollback(), TRUE, 'rolled back the transaction');
Tap::is($rollback, 6, 'rollback handler triggered with correct params');
Tap::is($commit, 0, 'commit handler not touched');
