#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;
use \Gaia\DB\Transaction;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_mysql_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

try {
    $db = new DB\Driver\PDO('mysql:host=127.0.0.1;dbname=test;port=3306');
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(18);

$rs = $db->execute('SELECT %s as foo, %s as bar', 'dummy\'', 'rummy');
Tap::ok( $rs, 'query executed successfully');
Tap::is($rs->fetch(PDO::FETCH_ASSOC), array('foo'=>'dummy\'', 'bar'=>'rummy'), 'sql query preparation works on strings');

$rs = $db->execute('SELECT %i as test', '1112122445543333333333');
Tap::is( $rs->fetch(PDO::FETCH_ASSOC), array('test'=>'1112122445543333333333'), 'query execute works injecting big integer in');

$rs = $db->execute('SELECT %i as test', 'dummy');
Tap::is( $rs->fetch(PDO::FETCH_ASSOC), array('test'=>'0'), 'query execute sanitizes non integer');

$rs = $db->execute('SELECT %f as test', '1112.122445543333333333');
Tap::is( $rs->fetch(PDO::FETCH_ASSOC), array('test'=>'1112.122445543333333333'), 'query execute works injecting big float in');

$rs = $db->execute('SELECT %f as test', 'dummy');
Tap::is( $rs->fetch(PDO::FETCH_ASSOC), array('test'=>'0'), 'query execute sanitizes non float');

$query = $db->format_query('%s', array('dummy', 'rummy'));
Tap::is($query, "'dummy', 'rummy'", 'format query handles arrays of strings');

$query = $db->format_query('%i', array(1,2,3));
Tap::is($query, '1, 2, 3', 'format query handles arrays of integers');

$query = $db->format_query('%f', array(1.545,2.2,3));
Tap::is($query, '1.545, 2.2, 3', 'format query handles arrays of floats');

$query = $db->format_query('test %%s ?, (?,?)', array(1, 2), 3, 4);
Tap::is($query, "test %s '1', '2', ('3','4')", 'format query question mark as string');

$db = new DB\Except( $db );

$err = NULL;
try {
    $db->execute('err');
} catch( Exception $e ){
    $err = (string) $e;
}

Tap::like($err, '/database error/i', 'When a bad query is run using execute() the except wrapper tosses an exception');

$stmt = $db->prepare('SELECT ? as test');
Tap::isa( $stmt, 'Gaia\DB\Driver\PDOStatement', 'prepared statement is gaia wrapper for pdo statements');
Tap::ok( $stmt->execute(array('t1') ), 'prepared statement runs ok');
Tap::is( $stmt->fetch(PDO::FETCH_ASSOC), array('test'=>'t1'), 'prepared statement returns result');
Transaction::start();
$db->start();
Transaction::rollback();
$stmt = $db->prepare('SELECT ? as test');
Tap::is( $stmt->execute(array('t1') ), FALSE, 'prepared statement returns false when transaction rollback happens');
Tap::is( $stmt->fetch(PDO::FETCH_ASSOC), FALSE, 'prepared statement returns no result when locked');


$db = new DB\Observe( $db );
Tap::is( $db->isa('pdo'), TRUE, 'isa returns true for inner class');
Tap::is( $db->isa('gaia\db\driver\pdo'), TRUE, 'isa returns true for driver');

//Tap::debug( $rs );


