#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DB;

if( ! class_exists('\MySQLi') ){
    Tap::plan('skip_all', 'php-mysqli not installed');
}

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}

DB\Connection::load( array(
    'test'=> function(){return new DB\Mock();}
));
$db = DB\Connection::instance('test');

Tap::plan(25);
Tap::ok( DB\Connection::instance('test') === $db, 'db instance returns same object we instantiated at first');

$db = new DB\Mock( function($method, $args){
    if( $method == 'execute' ) {
        return new DB\MockResult( array(array('foo'=>'dummy\'', 'bar'=>'rummy')  ) );
    }
    return FALSE;
});
$rs = $db->execute('SELECT %s as foo, %s as bar', 'dummy\'', 'rummy');
Tap::ok( $rs, 'query executed successfully');
Tap::is($rs->fetch_assoc(), array('foo'=>'dummy\'', 'bar'=>'rummy'), 'callback injection works to create result set');


$db = new DB\Mock( function($method, $args){
    if( $method == 'execute' ) {
        return new DB\MockResult( array( 
                array('id'=>'1'),
                array('id'=>'2'),
                array('id'=>'3'),
                ) );
    }
    return FALSE;
});

$rs = $db->execute('test');

Tap::is( $rs->fetch_row(), array('1'), 'fetch_row returns numerically keyed array');
Tap::is( $v = $rs->fetch_array(), array(0=>'2', 'id'=>'2'), 'fetch_array returns next row keyed and numeric keys too');
$obj = new stdclass;
@$obj->id = '3';
Tap::is( $rs->fetch_object(), $obj, 'data mapped into stdclass on fetch_object calls');
Tap::is( $rs->fetch_assoc(), FALSE, 'end of result set returns nothing');
Tap::is( $rs->fetch_all(MYSQLI_ASSOC), array( 
                array('id'=>'1'),
                array('id'=>'2'),
                array('id'=>'3'),
                ), 'fetch_all pulls down entire result set');
$rs = $db->execute('test again');

Tap::ok( $rs->free(), 'able to free result set');
Tap::is( $rs->fetch_assoc(), FALSE, 'after freeing fetch_assoc returns nothing');
Tap::is( $rs->fetch_all(), array(), 'fetch_All returns empty array()');


$rs = $db->execute('test');

Tap::is( $rs->fetch(\PDO::FETCH_NUM), array('1'), 'pdo fetch row returns numerically keyed array');
Tap::is( $v = $rs->fetch(\PDO::FETCH_BOTH), array(0=>'2', 'id'=>'2'), 'pdo fetch array returns next row keyed and numeric keys too');
$obj = new stdclass;
@$obj->id = '3';
Tap::is( $rs->fetch(\PDO::FETCH_OBJ), $obj, 'pdo data mapped into stdclass on fetch object calls');
Tap::is( $rs->fetch(\PDO::FETCH_ASSOC), FALSE, 'pdo end of result set returns nothing');
Tap::is( $rs->fetchAll(\PDO::FETCH_ASSOC), array( 
                array('id'=>'1'),
                array('id'=>'2'),
                array('id'=>'3'),
                ), 'pdo fetchAll pulls down entire result set');
$rs = $db->execute('test again');

Tap::ok( $rs->closeCursor(), 'pdo able to free result set');
Tap::is( $rs->fetch(PDO::FETCH_ASSOC), FALSE, 'pdo after freeing fetch returns nothing');
Tap::is( $rs->fetchAll(PDO::FETCH_OBJ), array(), 'pdo fetchAll returns empty array()');



$db = new DB\Mock();



$query = $db->format_query('%s', array('dummy', 'rummy'));
Tap::is($query, "'dummy', 'rummy'", 'format query handles arrays of strings');

$query = $db->format_query('%i', array(1,2,3));
Tap::is($query, '1, 2, 3', 'format query handles arrays of integers');

$query = $db->format_query('%f', array(1.545,2.2,3));
Tap::is($query, '1.545, 2.2, 3', 'format query handles arrays of floats');

Tap::is($db->begin(), TRUE, 'begin returns true if no callback specified');
Tap::is($db->commit(), TRUE, 'commit returns true if no callback specified');
Tap::is($db->rollback(), TRUE, 'rollback returns true if no callback specified');

