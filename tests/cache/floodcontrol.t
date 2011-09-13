#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;


Tap::plan(1);

$cache = new Cache\Memcache;
$cache->addServer('127.0.0.1', '11211');
$scope = 'floodcontrol/test/userid-' . microtime(TRUE) .'/';
$fc = new Cache\FloodControl( $cache, array('scope'=>$scope, 'max'=>2 ) );
$actual = array();
for( $i = 0; $i < 3; $i++){
    $actual[] = $fc->checkin();
    sleep(1);
}
$expected = array(
    true,
    true,
    false,
);

Tap::is( $actual, $expected, 'first two checkins work, last one fails on long flood control');
