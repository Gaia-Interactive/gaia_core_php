#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;
use Gaia\DB\Transaction;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';


Tap::plan(28);
$table = 'test_' . time() . '_' . mt_rand(10000, 99999);

$dbmain = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
$dbmain->query("create table $table (id int unsigned not null primary key) engine=innodb");
$conn1 = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
$conn2 = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');


Tap::ok( $conn1 !== $conn2, 'created two db objects');
$rs = $conn1->execute('SELECT CONNECTION_ID() as id');
$row = $rs->fetch_assoc();
$id1= $row['id'];

$rs = $conn2->execute('SELECT CONNECTION_ID() as id');
$row = $rs->fetch_assoc();
$id2= $row['id'];

Tap::isnt( $id1, $id2, 'got back connection ids from each and they arent the same');
Tap::ok($conn1->start(), 'started a transaction on conn1');
Tap::ok($conn2->start(), 'started a transaction on conn2');

$rs = $conn1->execute("insert into $table values (1)");
Tap::ok( $rs, 'inserted a row into test table from conn1');
//if( ! $rs ) Tap::debug( $conn1 );

$rs = $conn2->execute("insert into $table values(2)");
Tap::ok( $rs, 'inserted a row into test table from conn2');
//if( ! $rs ) Tap::debug( $conn2 );

Tap::ok( $rs = $conn1->commit(), 'committed inserted row on conn1');
//if( ! $rs ) Tap::debug( $conn1 );

Tap::ok( $rs = $conn2->rollback(), 'rolled back row on conn2');
//if( ! $rs ) Tap::debug( $conn2 );

Tap::ok($dbmain->execute("select id from $table"), 'selected all rows from the table');
$ct = $dbmain->affected_rows;
Tap::is($ct, 0, 'no rows in the table');
//if( ! $rs ) Tap::debug( $conn1 );

Transaction::reset();

$conn1 = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
$conn2 = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
Tap::ok($conn1->start(), 'started a transaction on conn1');
Tap::ok($conn2->start(), 'started a transaction on conn2');

$rs = $conn1->execute("insert into $table values (1)");
Tap::ok( $rs, 'inserted a row into test table from conn1');
//if( ! $rs ) Tap::debug( $conn1 );

$rs = $conn2->execute("insert into $table values(2)");
Tap::ok( $rs, 'inserted a row into test table from conn2');
//if( ! $rs ) Tap::debug( $conn2 );

Tap::ok( $conn1->commit(), 'committed inserted row on conn1');

Tap::ok( $conn2->commit(), 'committed inserted row on conn2');


Tap::ok($dbmain->execute("select id from $table"), 'selected all rows from the table');
$ct = $dbmain->affected_rows;
Tap::is($ct, 2, '2 rows in the table');
//var_dump( $rs );
//var_dump( $conn1 );

Transaction::reset();

Tap::ok(Transaction::start(), 'started a transaction at the global level');

$conn1 = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
$conn2 = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
$conn2 = new DB\Driver\MySQLi( $host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
Tap::ok($conn1->start(), 'started a transaction on conn1');
Tap::ok($conn2->start(), 'started a transaction on conn2');

$rs = $conn1->execute("insert into $table values (3)");
Tap::ok( $rs, 'inserted a row into test table from conn1');
//if( ! $rs ) Tap::debug( $conn1 );

$rs = $conn2->execute("insert into $table values(4)");
Tap::ok( $rs, 'inserted a row into test table from conn2');
//if( ! $rs ) Tap::debug( $conn2 );

Tap::ok( $conn1->commit(), 'committed inserted row on conn1');

Tap::ok( $conn2->commit(), 'committed inserted row on conn2');

Tap::ok( Transaction::rollback(), 'rolled back the transaction at the global level');

Tap::ok($dbmain->execute("select id from $table"), 'selected all rows from the table');
$ct = $dbmain->affected_rows;
Tap::is($ct, 2, '2 rows in the table, new rows rolled back');


$dbmain->execute("drop table $table");
