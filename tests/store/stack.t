#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;

$limit = 10;

Tap::plan(2);

$cache = new Store\KvpTTL;
$app = 'test/cache/stack/' . microtime(TRUE) .'/';
$cl = new Store\Stack( $cache );
$values = array();
for ($i=0; $i<=$limit;$i++) {
        $value = "value_$i";
        $pos = $cl->add($value);
        $values[$pos] = $value;
}

unset($cl);

$cl = new Store\Stack( $cache );
krsort( $values );
Tap::is($cl->recent(400), $values, 'all the items added to the list show up, sorted by most recently added');
Tap::is( $cl->count(), 11, 'got expected count of the items in the list');

