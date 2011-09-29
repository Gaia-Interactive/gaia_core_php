#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;

if( ! @fsockopen('127.0.0.1', '6379')) {
    Tap::plan('skip_all', 'Redis not running on localhost');
}

if( ! class_exists('Predis\Client') ){
    Tap::plan('skip_all', 'Predis library not loaded. check vendor/predis.');
}

Tap::plan(20);


$redis = new Cache\Redis;

$key = 'test/' . mt_rand(1,100000) . '/' . microtime(TRUE);
$value = mt_rand(1, 100);
Tap::ok($redis->set($key, $value), 'set an integer into redis');
Tap::is( $redis->get($key), $value, 'got back the value expected'); 
Tap::is( $redis->get( array( $key, 'non-existent' . $key ) ), array( $key=>$value ), 'multi-key get works');
Tap::is( $redis->increment($key, 2), $value + 2, 'increment by 2 worked');
Tap::is( $redis->get($key), $value + 2, 'read verifies the value set by increment');
Tap::ok($redis->set($key, $value), 'overwrote an integer into redis');
Tap::is( $redis->get($key), $value, 'got back the value expected'); 
Tap::is( $redis->decrement($key, 2), $value - 2, 'decrement by 2 worked');
Tap::is( $redis->get($key), $value - 2, 'read verifies the value set by decrement');
Tap::ok( $redis->set($key, array( $value ), 100), 'set an array');
Tap::is( $redis->get($key), array($value), 'got back the array i wrote in');
Tap::ok( $redis->delete( $key ), 'deleted the key');
Tap::is( $redis->get($key), FALSE, 'no data found after delete');
Tap::ok( $redis->add( $key, $value, 100), 'able to add the key back');
Tap::is( $redis->get($key), $value, 'got back the value expected after add'); 
Tap::ok( ! $redis->add( $key, $value, 100), 'not able to add the key after already added');
Tap::ok( $redis->replace( $key, $value + 1, 100), 'replace a key that already exists');
Tap::is( $redis->get($key), $value + 1, 'got back the value expected after replace'); 
$redis->delete( $key );
Tap::ok( ! $redis->replace( $key, $value, 100), 'replace a key that doesnt exist fails');
Tap::is( $redis->get($key), FALSE, 'nothing in the key after replace on a nonexistent key'); 
