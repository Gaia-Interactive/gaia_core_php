#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;

Tap::plan(6);

$cache = new Cache\Base();

Tap::ok( $cache instanceof Gaia\Cache\Base, 'instantiated base cache object');

$result = $cache->addServer('127.0.0.1', '11211');

Tap::ok( $result, 'connected to localhost server');

$data = array();
for( $i = 1; $i <= 3; $i++){
    $data[ 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000) ] = $i;
}

$res = FALSE;

foreach( $data as $k => $v ) {
    if( $cache->get( $k ) ) $res = TRUE;
}

Tap::ok( ! $res, 'none of the data exists before I write it in the cache');

$res = TRUE;
foreach( $data as $k => $v ){
    if( ! $cache->set( $k, $v, 0, 10) ) $res = FALSE;
}
Tap::ok( $res, 'wrote all of my data into the cache');

$res = TRUE;
foreach( $data as $k => $v ){
   if(  $cache->get( $k ) != $v ) $res = FALSE;
}
Tap::ok( $res, 'checked each key and got back what I wrote');

$ret = $cache->get( array_keys( $data ) );
$res = TRUE;
foreach( $data as $k => $v ){
    if( $ret[ $k ] != $v ) $res = FALSE;
}
Tap::ok( $res, 'grabbed the keys all at once, got what I wrote');