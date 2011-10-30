#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
define('BASEPATH', __DIR__ . '/../../vendor/CodeIgniter/system/');
define('APPPATH', __DIR__ . '/lib/codeigniter/app/');
@include BASEPATH . 'database/DB.php';
@include BASEPATH . 'core/Common.php';

use Gaia\Test\Tap;
use Gaia\DB;
use Gaia\DB\Transaction;

if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}

if( ! function_exists('DB') ){
	Tap::plan('skip_all', 'CodeIgniter database library not loaded');
}

if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}

Tap::plan(28);
$table = 'test_' . time() . '_' . mt_rand(10000, 99999);



$dbmain = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );
$dbmain->query("create table $table (id int unsigned not null primary key) engine=innodb");
$conn1 = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );
$conn2 = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );


Tap::ok( $conn1 !== $conn2, 'created two db objects');
$rs = $conn1->execute('SELECT CONNECTION_ID() as id');
$row = $rs->row_array();
$id1= $row['id'];

$rs = $conn2->execute('SELECT CONNECTION_ID() as id');
$row = $rs->row_array();
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

Tap::ok($rs = $dbmain->execute("select id from $table"), 'selected all rows from the table');
$ct = $rs->num_rows();
Tap::is($ct, 0, 'no rows in the table');
//if( ! $rs ) Tap::debug( $conn1 );

Transaction::reset();

$conn1 = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );
$conn2 = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );
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


Tap::ok($rs = $dbmain->execute("select id from $table"), 'selected all rows from the table');
$ct = $rs->num_rows();
Tap::is($ct, 2, '2 rows in the table');
//var_dump( $rs );
//var_dump( $conn1 );

Transaction::reset();

Tap::ok(Transaction::start(), 'started a transaction at the global level');

$conn1 = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );
$conn2 = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );
$conn2 = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );
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

Tap::ok($rs = $dbmain->execute("select id from $table"), 'selected all rows from the table');
$ct = $rs->num_rows();
Tap::is($ct, 2, '2 rows in the table, new rows rolled back');


$dbmain->execute("drop table $table");
