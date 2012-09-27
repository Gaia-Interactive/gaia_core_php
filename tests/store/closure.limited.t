#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$internal = new Store\KVPTTL();
$closures = array();
$closures['set'] = function( $k, $v, $ttl = 0 ) use( $internal ){ return $internal->set($k, $v, $ttl ); };
$closures['get'] = function( $input) use( $internal ){ return $internal->get($input ); };

$cache = new Store\Closure( $closures );



include __DIR__ . '/generic_tests.php';