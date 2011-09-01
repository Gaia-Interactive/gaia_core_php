#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DB;



try {
    DB\Connection::loadFile( __DIR__ . '/lib/config.php');
    $db = DB\Connection::instance('test');
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(4);

//print_r( $db );

$db = DB\Transaction::instance('test');

$rs = $db->query('CREATE TEMPORARY TABLE test1( id int unsigned NOT NULL PRIMARY KEY ) ENGINE=INNODB');
$db->query('INSERT into test1 (id) VALUES(1)');
$rs = $db->query('SELECT id from test1 WHERE id = 1');
Tap::is( $rs->fetch_assoc(), array('id'=>1), 'inserted a new row and selected it back');

DB\Transaction::rollback();

$rs = $db->query('SELECT id from test1 WHERE id = 1');
Tap::is( $rs->fetch_assoc(), NULL, 'after rollback, no row found');
$db->close();

$db = DB\Transaction::instance('test');
$rs = $db->query('CREATE TEMPORARY TABLE test1( id int unsigned NOT NULL PRIMARY KEY ) ENGINE=INNODB');
$db->query('INSERT into test1 (id) VALUES(1)');
$rs = $db->query('SELECT id from test1 WHERE id = 1');
Tap::is( $rs->fetch_assoc(), array('id'=>1), 'inserted a new row and selected it back');
DB\Transaction::commit();
$rs = $db->query('SELECT id from test1 WHERE id = 1');
Tap::is( $rs->fetch_assoc(), array('id'=>1), 'after commiting can still select the row');
