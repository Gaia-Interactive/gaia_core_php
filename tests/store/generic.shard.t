#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;


$a = new Store\KVPTTL;
$b = new Store\KVPTTL;

$closure = function( $key ) use ( $a, $b ){
    $hash = abs( crc32( $key) ) % 2;
    return $hash ? $a : $b;
};


$cache = new Store\Shard( $closure );

include __DIR__ . '/generic_tests.php';
