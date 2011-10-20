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

Tap::plan(3);

$db = new DB\Except( $mock = new DB\Mock( function($method, $args){
    return FALSE;
}));

$err = '';

$debug = null;

try {
    $db->execute('test');
} catch( Exception $e ){
    $err = (string) $e;
    $debug = $e->getDebug();
}

Tap::like( $err, '/database error/i', 'except wrapping db object, on query failure an exception is thrown');
Tap::is( $debug, array('db'=>$mock, 'query'=>'test', 'exception'=>null), 'debug attached properly to exception');


$db = new DB\Except( $mock = new DB\Mock( function($method, $args){
    return TRUE;
}));

$err = '';

$debug = null;

try {
    $db->execute('test');
} catch( Exception $e ){
    $err = (string) $e;
    $debug = $e->getDebug();
}

Tap::is( $err, '', 'no exception thrown when query runs properly');

//print $err;