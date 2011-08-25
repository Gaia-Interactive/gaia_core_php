#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;

Tap::plan(21);

$ids = array('1', '2');

$m = new Cache\Memcache();
$m->addServer('127.0.0.1', '11211');
$m = new Cache\Replica( $o = new Cache\Observe( $m ), 2);

Tap::ok( $m instanceof Cache\Iface, 'wrapped the object in an observer');
$key = 'test' . microtime(TRUE);
$res = $m->get($key);
Tap::ok( $res === FALSE, 'send get request for a key, got no data back');
$calls = $o->calls();

$call = array_shift( $calls );
Tap::ok( is_array( $call ), 'first call went through');
Tap::is($call['method'], 'get', 'trying to retrieve our replica');
Tap::like($call['args'][0][0], '#^' . str_replace('.', '\.', $key ) . '/REPLICA/[\d]$#', 'first key for fetching the data');
Tap::is( $call['result'], array(), 'response is an empty array');

$call = array_shift( $calls );

Tap::ok( is_array($call), 'second call went through');
Tap::is($call['method'], 'get', 'trying to retrieve our replica');
Tap::like($call['args'][0][0], '#^' . str_replace('.', '\.', $key ) . '/REPLICA/[\d]$#', 'first key for fetching the data');
Tap::is( $call['result'], array(), 'response is an empty array');

$call = array_shift( $calls );
Tap::ok( ! $call, 'that was the last call in the stack');

$res = $m->set( $key, $value = time(), $ttl = 300);
$expire = $ttl + time();
Tap::ok( $res, 'set returned a true response');

$calls = $o->calls();
$call = array_shift( $calls );

Tap::ok( is_array($call ), 'next call went through');
Tap::is( $call['method'], 'set', 'it is a set request');
Tap::is( $call['result'], TRUE, 'response to set key is true');
Tap::is( $call['args'], array($key . '/REPLICA/1', $value, $ttl ), 'set call for first data replica worked properly');

$call = array_shift( $calls );

Tap::ok( is_array($call ), 'next call went through');
Tap::is( $call['method'], 'set', 'it is a set request');
Tap::is( $call['result'], TRUE, 'response to set key is true');
Tap::is( $call['args'], array($key . '/REPLICA/2', $value, $ttl ), 'set call for second data replica worked properly');


$call = array_shift( $calls );

Tap::ok( ! $call, 'no calls left');

//print_r( $calls );