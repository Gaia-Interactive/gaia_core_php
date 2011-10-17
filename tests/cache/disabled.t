#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;

Tap::plan(7);

$cache = new Cache\Disabled();

Tap::ok( $cache instanceof Cache\Disabled, 'instantiated disabled cache object');

$data = array();
for( $i = 1; $i <= 3; $i++){
    $data[ 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000) ] = $i;
}

$res = FALSE;

foreach( $data as $k => $v ) {
    if( $cache->get( $k ) ) $res = TRUE;
}

Tap::ok( ! $res, 'none of the data exists before I write it in the cache');

$res = FALSE;
foreach( $data as $k => $v ){
    if( $cache->set( $k, $v, 10) ) $res = TRUE;
}
Tap::ok( ! $res, 'unable to write any of my data into the cache');

$res = FALSE;
foreach( $data as $k => $v ){
   if(  $cache->get( $k ) ) $res = TRUE;
}
Tap::ok( ! $res, 'checked each key and got back nothing');

$ret = $cache->get( array_keys( $data ) );

Tap::is( $res, array(), 'grabbed the keys all at once, got an empty array');

$k = 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000);
Tap::ok( ! $cache->add( $k, 1, 10), 'adding a non-existent key, fails first time');
Tap::ok( ! $cache->add( $k, 1, 10), 'second time, the add fails as well');

