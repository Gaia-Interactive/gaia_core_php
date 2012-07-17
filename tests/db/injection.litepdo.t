#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;
use Gaia\DB\Transaction;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_sqlite_installed.php';

$instance = function(){
    return new \PDO('sqlite::memory:');
};

try {
    $db = $instance();
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}


$original = $db;


Tap::plan(26);
$db = new DB($db);

DB\Connection::load( array( 'test'=> function() use( $db ){ return $db; }));

Tap::ok( DB\Connection::instance('test') === $db, 'db instance returns same object we instantiated at first');

$rs = $db->execute('SELECT %s as foo, %s as bar', 'dummy\'', 'rummy');
Tap::ok( $rs, 'query executed successfully');
Tap::is($rs->fetch(), array('foo'=>'dummy\'', 'bar'=>'rummy'), 'sql query preparation works on strings');

$rs = $db->execute('SELECT %i as test', '111212244554333');
Tap::is( $rs->fetch(), array('test'=>'111212244554333'), 'query execute works injecting big integer in');

$rs = $db->execute('SELECT %i as test', 'dummy');
Tap::is( $rs->fetch(), array('test'=>'0'), 'query execute sanitizes non integer');

$rs = $db->execute('SELECT %f as test', '1112.1224455433');
Tap::is( $rs->fetch(), array('test'=>'1112.1224455433'), 'query execute works injecting big float in');

$rs = $db->execute('SELECT %f as test', 'dummy');
Tap::is( $rs->fetch(), array('test'=>'0'), 'query execute sanitizes non float');

$query = $db->prep('%s', array('dummy', 'rummy'));
Tap::is($query, "'dummy', 'rummy'", 'format query handles arrays of strings');

$query = $db->prep('%i', array(1,2,3));
Tap::is($query, '1, 2, 3', 'format query handles arrays of integers');

$query = $db->prep('%f', array(1.545,2.2,3));
Tap::is($query, '1.545, 2.2, 3', 'format query handles arrays of floats');


$query = $db->prep('test %%s ?, (?,?)', array(1, 2), 3, 4);
Tap::is($query, "test %s '1', '2', ('3','4')", 'format query question mark as string');

$rs = $db->execute('err');

Tap::cmp_ok( $rs, '===', FALSE, 'db returns false on query error');

Tap::like( $db->error(), '/syntax/i', '$db->error() returns error message');

Tap::is( $db->errorcode(), 1, 'returns expected error code');

$db = new DB\Except( DB\Connection::instance('test') );

$err = NULL;
try {
    $db->execute('err');
} catch( Exception $e ){
    $err = (string) $e;
}

Tap::like($err, '/database error/i', 'When a bad query is run using execute() the except wrapper tosses an exception');


Tap::is( $db->isa(get_class($original)), TRUE, 'isa returns true for original object');
Tap::is( $db->isa('gaia\db'), TRUE, 'isa returns true for gaia\db');

$newconn = function() use( $instance ){
    return new DB( $instance() );
};

Transaction::reset();
$table = 'test_' . time() . '_' . mt_rand(10000, 99999);


$db = $newconn();
$db->execute("create table $table (id int unsigned not null primary key)");


Tap::ok($db->start(), 'started a transaction');

$rs = $db->execute("insert into $table values (1)");
Tap::ok( $rs, 'inserted a row into test table');
//if( ! $rs ) Tap::debug( $conn1 );

Tap::ok( $rs = $db->commit(), 'committed inserted row');
//if( ! $rs ) Tap::debug( $conn1 );

Tap::ok($rs = $db->execute("select id from $table"), 'selected all rows from the table');
$ct = count( $rs->all());
Tap::is($ct, 1, '1 row in the table');
//if( ! $rs ) Tap::debug( $conn1 );

//Transaction::reset();


$db->start();

$rs = $db->execute("insert into $table values (2)");
Tap::ok( $rs, 'inserted a row into test table');
//if( ! $rs ) Tap::debug( $conn1 );

//if( ! $rs ) Tap::debug( $conn2 );

Tap::ok( $db->rollback(), 'rolled back inserted row');


$db = new DB( $db->core() );
Tap::ok($rs = $db->execute("select id from $table"), 'selected all rows from the table');
$ct = count( $rs->all() );
Tap::is($ct, 1, '1 row in the table');
//var_dump( $rs );
//var_dump( $conn1 );


$db->execute("drop table $table");




