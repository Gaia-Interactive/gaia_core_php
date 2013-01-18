#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\Exception;

Tap::plan(10);

$e = new Exception();

Tap::ok( $e instanceof \Exception, 'instantiated an empty exception');
Tap::is( $e->getErrorParameters(), array(), 'got error parameters back');
Tap::is( $e->getMessage(), '', 'empty message, none passed in');
Tap::is( $e->getDebug(), NULL, 'no debug set');
Tap::like( $e->__toString(), "#gaia([\\\\])exception#i", '__toString method implemented');

$e = new Exception(array('message'=>'test', 'debug'=>'foo', 'bar'=>'bazz') );

Tap::is( $e->getMessage(), 'test', 'instantiated an exception with the message in an array');
Tap::is( $e->getDebug(), 'foo', 'got debug from the array');
$p = $e->getErrorParameters();
Tap::is( $p['bar'], 'bazz', 'got a parameter from the exception');

$e = new Exception('test', $debug = new stdclass);
Tap::is( $e->getMessage(), 'test', 'standard instantiation, passes message in properly');
Tap::is( $e->getDebug(), $debug, 'got the debug object');