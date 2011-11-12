<?php
use Gaia\Test\Tap;
use Gaia\DB;
use Gaia\DB\Transaction;

$original = $db;


Tap::plan(50);
$db = new DB($db);

DB\Connection::load( array( 'test'=> function() use( $db ){ return $db; }));

Tap::ok( DB\Connection::instance('test') === $db, 'db instance returns same object we instantiated at first');

Tap::is( $db->isa('mysql'), TRUE, 'driver is mysql');

$rs = $db->execute('SELECT %s as foo, %s as bar', 'dummy\'', 'rummy');
Tap::ok( $rs, 'query executed successfully');
Tap::is($rs->fetch(), array('foo'=>'dummy\'', 'bar'=>'rummy'), 'sql query preparation works on strings');

$rs = $db->execute('SELECT %i as test', '1112122445543333333333');
Tap::is( $rs->fetch(), array('test'=>'1112122445543333333333'), 'query execute works injecting big integer in');

$rs = $db->execute('SELECT %i as test', 'dummy');
Tap::is( $rs->fetch(), array('test'=>'0'), 'query execute sanitizes non integer');

$rs = $db->execute('SELECT %f as test', '1112.122445543333333333');
Tap::is( $rs->fetch(), array('test'=>'1112.122445543333333333'), 'query execute works injecting big float in');

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

Tap::like( $db->error(), '/you have an error in your sql syntax/i', '$db->error() returns error message');

Tap::is( $db->errorcode(), 1064, 'returns expected mysql error code');

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

$rs = $db->execute("SELECT 'test' as r1");
Tap::is( $rs->affected(), 1, 'selected a row, affected rows is one');

$newconn = function() use( $instance ){
    return new DB( $instance() );
};


$table = 'test_' . time() . '_' . mt_rand(10000, 99999);


$dbmain = $newconn();
$dbmain->execute("create table $table (id int unsigned not null primary key) engine=innodb");
$conn1 = $newconn();
$conn2 = $newconn();


Tap::ok( $conn1 !== $conn2, 'created two db objects');
$rs = $conn1->execute('SELECT CONNECTION_ID() as id');
$row = $rs->fetch();
$id1= $row['id'];

$rs = $conn2->execute('SELECT CONNECTION_ID() as id');
$row = $rs->fetch();
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
$ct = $rs->affected();
Tap::is($ct, 0, 'no rows in the table');
//if( ! $rs ) Tap::debug( $conn1 );

Transaction::reset();

$conn1 = $newconn();
$conn2 = $newconn();
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
$ct = $rs->affected();
Tap::is($ct, 2, '2 rows in the table');
//var_dump( $rs );
//var_dump( $conn1 );

Transaction::reset();

Tap::ok(Transaction::start(), 'started a transaction at the global level');

$conn1 = $newconn();
$conn2 = $newconn();
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
$ct = $rs->affected();
Tap::is($ct, 2, '2 rows in the table, new rows rolled back');

$rs = $conn1->execute("select id from $table");
Tap::is( $rs, FALSE, 'after rolling back, new queries fail on rolled back db object');


$dbmain->execute("drop table $table");


$db = $newconn();

$raw = file_get_contents(__DIR__ . '/../sample/i_can_eat_glass.txt');

$lines = explode("\n", $raw);
$lines = array_slice($lines, 0, 10) + array_slice($lines, 100, 10) + array_slice($lines, 200, 10) + array_slice($lines, 200, 10);
$raw = implode("\n", $lines);
$sql = "CREATE TEMPORARY TABLE t1utf8 (`i` INT UNSIGNED NOT NULL PRIMARY KEY, `line` VARCHAR(5000) ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8";
$db->execute($sql);

foreach($lines as $i=>$line ){
    //$lines[ $i ] = $line = mb_convert_encoding($line, 'UTF-8', 'auto');
    $db->execute('INSERT INTO t1utf8 (`i`, `line`) VALUES (%i, %s)', $i, $line);
}


$rs = $db->execute('SELECT * FROM t1utf8');
$readlines = array();
while( $row = $rs->fetch() ){
    $readlines[ $row['i'] ] = $row['line'];
}
$rs->free();

Tap::cmp_ok( $readlines, '===', $lines, 'inserted all the rows and read them back, worked as expected');
//Tap::debug( $readlines );



$rs = $db->execute('SELECT %s AS `d`', $raw);
$row = $rs->fetch();
$rs->free();

Tap::cmp_ok( $row['d'], '===', $raw, 'passed a huge chunk of utf-8 data to db and asked for it back. got what I sent.');
//Tap::debug( $row['d'] );


