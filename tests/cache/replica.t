#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;

Tap::plan(17);

$m = new Cache\Memcache();
$m->addServer('127.0.0.1', '11211');
$m = new Cache\Replica($m, 2);
Tap::ok( $m instanceof Cache\Iface, 'replicamemcache instantiated successfully');

$key = 'test' . microtime(TRUE);
$res = $m->get($key);
Tap::ok( $res === FALSE, 'send get request for a key, got no data back');
$res = $m->set( $key, 1, 30);
Tap::ok( $res, 'write data into the key, got an ok response back');
$res = $m->get($key);
Tap::is( $res, 1, 'read the data again, got my value back');

$key = 'test' . microtime(TRUE);
$res = $m->add( $key, 1, 30);
Tap::is( $res, TRUE, 'adding a new key');

$res = $m->add( $key, 1, 30);
Tap::is( $res, FALSE, 'trying to add the key again fails');
$res = $m->get($key);

Tap::is( $res, 1, 'key has the value we added earlier');


$res = $m->replace( $key, 2, 0, 30 );
Tap::is( $res, TRUE, 'replacing the key works');
$res = $m->get($key);

Tap::is( $res, 2, 'key now has new value of replacement');

$res = $m->increment($key);
Tap::is( $res, 3, 'incrementing the key');
$res = $m->get( $key );
Tap::is( $res, 3, 'key now matches the value we incremented to');

$res = $m->increment($key, 7);
Tap::is( $res, 10, 'incrementing the key by 7');
$res = $m->get($key);
Tap::is( $res, 10, 'reading the data back returns 10');

$res = $m->decrement($key, 7);
Tap::is( $res, 3, 'decrementing the key by 7');
$res = $m->get($key);
Tap::is( $res, 3, 'reading the data back returns 3');

$res = $m->decrement($key);
Tap::is( $res, 2, 'decrementing the key');
$res = $m->get( $key );
Tap::is( $res, 2, 'key now matches the value we decremented to');
