#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;

$limit = 10;

Tap::plan(2);

$cache = new Cache\Memcache;
$result = $cache->addServer('127.0.0.1', '11211');
Tap::ok( $result, 'connected to localhost server');
$app = 'test/cache/stack/' . microtime(TRUE) .'/';
$cl = new Cache\Stack( new Cache\Namespaced( $cache, $app ) );
$values = array();
for ($i=0; $i<=$limit;$i++) {
        $value = "value_$i";
        $pos = $cl->add($value);
        $values[$pos] = $value;
}

unset($cl);

$cl = new Cache\Stack( new Cache\Namespaced( $cache, $app ) );
krsort( $values );
Tap::is($cl->recent(400), $values, 'all the items added to the list show up, sorted by most recently added');
