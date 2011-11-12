#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$internal = new Store\KVPTTL();

$closures = array();

$closures['set'] = function( $k, $v, $ttl = 0 ) use( $internal ){ return $internal->set($k, $v, $ttl ); };
$closures['add'] = function( $k, $v, $ttl = 0 ) use( $internal ){ return $internal->add($k, $v, $ttl ); };
$closures['replace'] = function( $k, $v, $ttl = 0 ) use( $internal ){ return $internal->replace($k, $v, $ttl ); };
$closures['get'] = function( $input) use( $internal ){ return $internal->get($input ); };
$closures['increment'] = function( $k, $v = 1 ) use( $internal ){ return $internal->increment($k, $v ); };
$closures['decrement'] = function( $k, $v = 1 ) use( $internal ){ return $internal->decrement($k, $v ); };
$closures['flush'] = function() use( $internal ){ return $internal->flush(); };
$closures['delete'] = function($k) use( $internal ){ return $internal->delete($k); };
$closures['ttlenabled'] = function() use ($internal ){ return $internal->ttlEnabled(); };


$cache = new Store\Closure( $closures );



include __DIR__ . '/generic_tests.php';