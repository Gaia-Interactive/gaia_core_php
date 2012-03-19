#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;

Tap::plan(36);

$a = new Store\KvpTTL;
$b = new Store\KvpTTL;

$m = new Store\Replica(array($a, $b));
Tap::ok( $m instanceof Store\Iface, 'Store\replica instantiated successfully');

$key = 'test' . microtime(TRUE);
$res = $m->get($key);
Tap::ok( $res === NULL, 'send get request for a key, got no data back');
$res = $m->set( $key, 1, 30);
Tap::ok( $res, 'write data into the key, got an ok response back');
$res = $m->get($key);
Tap::is( $res, 1, 'read the data again, got my value back');
Tap::is( $a->get($key), 1, 'correct value found in the first replica');
Tap::is( $b->get($key), 1, 'correct value found in the second replica');
Tap::is( $m->get( array( $key, 'non-existent-' . $key) ), array( $key=>1), 'multi-get returns correct data');

$key = 'test' . microtime(TRUE);

Tap::ok( ! $m->replace( $key, 1, 30 ), 'replacing non-existent key fails');

$res = $m->add( $key, 1, 30);
Tap::is( $res, TRUE, 'adding a new key');

$res = $m->add( $key, 1, 30);
Tap::is( $res, FALSE, 'trying to add the key again fails');
$res = $m->get($key);

Tap::is( $res, 1, 'key has the value we added earlier');
Tap::is( $a->get($key), 1, 'correct value found in the first replica');
Tap::is( $b->get($key), 1, 'correct value found in the second replica');


$res = $m->replace( $key, 2, 0, 30 );
Tap::is( $res, TRUE, 'replacing the key works');
$res = $m->get($key);

Tap::is( $res, 2, 'key now has new value of replacement');
Tap::is( $a->get($key), 2, 'correct value found in the first replica');
Tap::is( $b->get($key), 2, 'correct value found in the second replica');
$res = $m->increment($key);
Tap::is( $res, 3, 'incrementing the key');
$res = $m->get( $key );
Tap::is( $res, 3, 'key now matches the value we incremented to');
Tap::is( $a->get($key), 3, 'correct value found in the first replica');
Tap::is( $b->get($key), 3, 'correct value found in the second replica');

$res = $m->increment($key, 7);
Tap::is( $res, 10, 'incrementing the key by 7');
$res = $m->get($key);
Tap::is( $res, 10, 'reading the data back returns 10');
Tap::is( $a->get($key), 10, 'correct value found in the first replica');
Tap::is( $b->get($key), 10, 'correct value found in the second replica');

$res = $m->decrement($key, 7);
Tap::is( $res, 3, 'decrementing the key by 7');
$res = $m->get($key);
Tap::is( $res, 3, 'reading the data back returns 3');
Tap::is( $a->get($key), 3, 'correct value found in the first replica');
Tap::is( $b->get($key), 3, 'correct value found in the second replica');

$res = $m->decrement($key);
Tap::is( $res, 2, 'decrementing the key');
$res = $m->get( $key );
Tap::is( $res, 2, 'key now matches the value we decremented to');
Tap::is( $a->get($key), 2, 'correct value found in the first replica');
Tap::is( $b->get($key), 2, 'correct value found in the second replica');

Tap::ok($m->delete( $key ), 'successfully deleted the key');
Tap::is( $a->get($key), FALSE, 'correct value found in the first replica');
Tap::is( $b->get($key), FALSE, 'correct value found in the second replica');

