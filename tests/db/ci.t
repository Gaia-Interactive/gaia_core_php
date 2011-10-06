#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
define('BASEPATH', __DIR__ . '/../../vendor/CodeIgniter/system/');
define('APPPATH', __DIR__ . '/lib/codeigniter/app/');
@include BASEPATH . 'database/DB.php';
@include BASEPATH . 'core/Common.php';

use Gaia\Test\Tap;
use Gaia\DB;

if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}

if( ! function_exists('DB') ){
	Tap::plan('skip_all', 'CodeIgniter database library not loaded');
}

try {
    DB\Connection::load( array(
        'test'=> function(){
                $db = new DB\Driver\CI( DB( array(
                            'dbdriver'	=> 'mysql',
							'hostname'	=> '127.0.0.1',
							'database'	=> 'test') ) );
				return $db;
        }
    ));
    $db = DB\Connection::instance('test');
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(10);

Tap::ok( DB\Connection::instance('test') === $db, 'db instance returns same object we instantiated at first');

$rs = $db->execute('SELECT %s as foo, %s as bar', 'dummy\'', 'rummy');
Tap::ok( $rs, 'query executed successfully');

Tap::is($rs->row_array(), array('foo'=>'dummy\'', 'bar'=>'rummy'), 'sql query preparation works on strings');

$rs = $db->execute('SELECT %i as test', '1112122445543333333333');
Tap::is( $rs->row_array(), array('test'=>'1112122445543333333333'), 'query execute works injecting big integer in');

$rs = $db->execute('SELECT %i as test', 'dummy');
Tap::is( $rs->row_array(), array('test'=>'0'), 'query execute sanitizes non integer');

$rs = $db->execute('SELECT %f as test', '1112.122445543333333333');
Tap::is( $rs->row_array(), array('test'=>'1112.122445543333333333'), 'query execute works injecting big float in');

$rs = $db->execute('SELECT %f as test', 'dummy');
Tap::is( $rs->row_array(), array('test'=>'0'), 'query execute sanitizes non float');

$query = $db->format_query('%s', array('dummy', 'rummy'));
Tap::is($query, "'dummy', 'rummy'", 'format query handles arrays of strings');

$query = $db->format_query('%i', array(1,2,3));
Tap::is($query, '1, 2, 3', 'format query handles arrays of integers');

$query = $db->format_query('%f', array(1.545,2.2,3));
Tap::is($query, '1.545, 2.2, 3', 'format query handles arrays of floats');

