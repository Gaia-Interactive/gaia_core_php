#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;

if( ! @fsockopen('127.0.0.1', '11211')) {
    Tap::plan('skip_all', 'Memcached not running on localhost');
}

if( ! class_exists('\Memcache') && ! class_exists('\Memcached') ){
    Tap::plan('skip_all', 'no pecl-memcache or pecl-memcached extension installed');
}

Tap::plan(13);

$cache = new Store\Memcache();

Tap::ok( $cache instanceof Store\Memcache, 'instantiated memcache cache object');

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
    if( ! $cache->set( $k, $v, 10) ) $res = FALSE;
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

$k = 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000);
Tap::ok( $cache->add( $k, 1, 10), 'adding a non-existent key');
Tap::ok( ! $cache->add( $k, 1, 10), 'second time, the add fails');

$cache = new Store\Memcache();
for( $i = 1; $i < 4; $i++){
    $cache->addServer('10.0.0.' . $i, '11211', 1);
}

Tap::is( count( $cache->servers() ), 3, 'added 3 connections to server');

$replicas = $cache->replicas(2);

Tap::is( $replicas[0]->servers(), array( array( 'host' => '10.0.0.1', 'port' => 11211, 'weight' => 1), array( 'host' => '10.0.0.3', 'port' => 11211, 'weight' => 1)  ), 'first replica has the correct servers in it');
Tap::is( $replicas[1]->servers(), array( array( 'host' => '10.0.0.2', 'port' => 11211, 'weight' => 1) ), 'second replica has the correct servers in it');

if( class_exists('\Memcache') ){
    $m = new Store\Memcache( new \Memcache );
    Tap::ok( $m , 'able to instantiate and inject the memcache object into Store\Memcache');
} else {
    Tap::pass('skipping memcache injection check');
}

if( class_exists('\Memcached') ){
    $m = new Store\Memcache( new \Memcached );
    Tap::ok( $m , 'able to instantiate and inject the memcached object into Store\Memcache');
} else {
    Tap::pass('skipping memcached injection check');
}
