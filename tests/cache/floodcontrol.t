#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;


Tap::plan(1);

$cache = new Cache\Mock;
$scope = 'floodcontrol/test/userid-' . microtime(TRUE) .'/';
$fc = new Cache\FloodControl( $cache, array('scope'=>$scope, 'max'=>2 ) );
$actual = array();
for( $i = 0; $i < 3; $i++){
    $actual[] = $fc->go();
    Cache\Mock::$time_offset++;
}
$expected = array(
    true,
    false,
    true,
);

Tap::is( $actual, $expected, 'first attempt works, second fails on short flood, last one fails on long flood control');
