#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DB;

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}

if( ! in_array( 'sqlite', PDO::getAvailableDrivers()) ){
    Tap::plan('skip_all', 'this version of PDO does not support sqlite');
}

try {
    DB\Connection::load( array(
    'test'=> function () {
        $db = new Gaia\DB\Driver\PDO( 'sqlite::memory:');
        $db->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Gaia\DB\Driver\PDOStatement', array($db)));
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT );
        return $db;
    }
    ) );
    $db = DB\Connection::instance('test');
} catch( \Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(10);
Tap::ok( DB\Connection::instance('test') === $db, 'db instance returns same object we instantiated at first');

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
