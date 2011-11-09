#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/pdo_installed.php';

Tap::plan(5);

$db = new DB\Except( $mock = new DB\Callback());

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


$db = new DB\Except( $mock = new DB\Callback(array('execute'=> function($query){
    return TRUE;
})));

$err = '';

$debug = null;

try {
    $db->execute('test');
} catch( Exception $e ){
    $err = (string) $e;
    $debug = $e->getDebug();
}

Tap::is( $err, '', 'no exception thrown when query runs properly');

Tap::is( $db->isa('Gaia\DB\Callback'), TRUE, 'expect is a wrapper and tells us the core instanceof');
Tap::is( $db->isa('Gaia\DB\Transaction'), FALSE, 'doesnt false report instanceof');

//print $err;