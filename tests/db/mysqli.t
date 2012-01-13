#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';


try {
    DB\Connection::load( array(
        'test'=> function(){
             $db = new DB\Driver\MySQLi( 
                $host = '127.0.0.1', 
                $user = NULL, 
                $pass = NULL, 
                $db = 'test', 
                '3306');
                return $db;
        }
    ));
    $db = DB\Connection::instance('test');
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(16);
Tap::ok( DB\Connection::instance('test') === $db, 'db instance returns same object we instantiated at first');

$rs = $db->execute('SELECT %s as foo, %s as bar', 'dummy\'', 'rummy');
Tap::ok( $rs, 'query executed successfully');
Tap::is($rs->fetch_assoc(), array('foo'=>'dummy\'', 'bar'=>'rummy'), 'sql query preparation works on strings');

$rs = $db->execute('SELECT %i as test', '1112122445543333333333');
Tap::is( $rs->fetch_assoc(), array('test'=>'1112122445543333333333'), 'query execute works injecting big integer in');

$rs = $db->execute('SELECT %i as test', 3.70E+9);
Tap::is( $rs->fetch_assoc(), array('test'=>'3700000000'), 'query execute works injecting scientific notation integer in');


$rs = $db->execute('SELECT %i as test', -1.0E+18);
Tap::is( $rs->fetch_assoc(), array('test'=>'-1000000000000000000'), 'query execute works injecting huge scientific notation integer in');

$rs = $db->execute('SELECT %i as test', 'dummy');
Tap::is( $rs->fetch_assoc(), array('test'=>'0'), 'query execute sanitizes non integer');

$rs = $db->execute('SELECT %f as test', '1112.122445543333333333');
Tap::is( $rs->fetch_assoc(), array('test'=>'1112.122445543333333333'), 'query execute works injecting big float in');

$rs = $db->execute('SELECT %f as test', 'dummy');
Tap::is( $rs->fetch_assoc(), array('test'=>'0'), 'query execute sanitizes non float');

$query = $db->prep('%s', array('dummy', 'rummy'));
Tap::is($query, "'dummy', 'rummy'", 'format query handles arrays of strings');

$query = $db->prep('%i', array(1,2,3));
Tap::is($query, '1, 2, 3', 'format query handles arrays of integers');

$query = $db->prep('%f', array(1.545,2.2,3));
Tap::is($query, '1.545, 2.2, 3', 'format query handles arrays of floats');


$query = $db->prep('test %%s ?, (?,?)', array(1, 2), 3, 4);
Tap::is($query, "test %s '1', '2', ('3','4')", 'format query question mark as string');


$db = new DB\Except( DB\Connection::instance('test') );

$err = NULL;
try {
    $db->execute('err');
} catch( Exception $e ){
    $err = (string) $e;
}

Tap::like($err, '/database error/i', 'When a bad query is run using execute() the except wrapper tosses an exception');

Tap::is( $db->isa('mysqli'), TRUE, 'isa returns true for mysqli');
Tap::is( $db->isa('gaia\db\driver\mysqli'), TRUE, 'isa returns true for gaia\db\driver\mysqli');

