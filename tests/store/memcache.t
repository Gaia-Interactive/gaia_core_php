#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/memcache_installed.php';
include __DIR__ . '/../assert/memcache_running.php';


Tap::plan(25);

$cache = new Store\Memcache();

Tap::ok( $cache instanceof Store\Memcache, 'instantiated memcache cache object');

$result = $cache->addServer('127.0.0.1', '11211');

Tap::ok( $result, 'connected to localhost server');

$cache = new Store\Memcache('127.0.0.1:11211:2');
Tap::is( $cache->servers(), array(array('host'=>'127.0.0.1', 'port'=>'11211', 'weight'=>'2')), 'connected to localhost server by passing a string to the constructor');


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
    $m = new Store\Memcache( $core = new \Memcache );
    Tap::cmp_ok( $m->core(), '===', $core , 'able to instantiate and inject the memcache object into Store\Memcache');
} else {
    Tap::pass('skipping memcache injection check');
}

if( class_exists('\Memcached') ){
    $m = new Store\Memcache( $core = new \Memcached );
    Tap::cmp_ok( $m->core(), '===', $core, 'able to instantiate and inject the memcached object into Store\Memcache');
} else {
    Tap::pass('skipping memcached injection check');
}



if( class_exists('Memcache') ){
    $verify = new \Memcache;
} else {
    $verify = new \Memcached;
}

$verify->addServer('127.0.0.1', '11211');
$m = new Store\Memcache($verify);

$key = sha1('test' . microtime(TRUE) . __FILE__);

$verify->set( $key, 1 );

Tap::is( $m->get( $key ), 1, 'Write into memcache and verify the store\memcache class can read it');

$m->set($key, 2);

Tap::is($verify->get($key), 2, 'change the value with store\memcache and verify it changed in memcache');

$m->increment($key);

Tap::is( $verify->get($key), 3, 'incremented the key and verified correct value in memcache');


$m->increment($key, 2);

Tap::is( $verify->get($key), 5, 'incremented the key by several and verified correct value in memcache');

$m->decrement($key);

Tap::is( $verify->get($key), 4, 'decremented the key and verified correct value in memcache');


$m->decrement($key, 2);

Tap::is( $verify->get($key), 2, 'decremented the key by serveral and verified correct value in memcache');

$m->replace( $key, 100);

Tap::is( $verify->get( $key ), 100, 'replaced the value and verified correct value shows up in memcache');


$m->delete($key);

Tap::cmp_ok($verify->get($key), '===', FALSE, 'delete the key using store\memcache and verify it is gone');


$m->replace( $key, 100);

Tap::cmp_ok( $verify->get( $key ), '===', FALSE, 'attempted replace after delete and verified nothing shows up in memcache');


$m->add( $key, 100);

Tap::is( $verify->get( $key ), 100, 'added the key after delete and verified it shows up in memcache');

$m->add( $key, 200);

Tap::is( $verify->get( $key ), 100, 'added the key again with differrent value and verified nothing changes in memcache');

