#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\Time;

Tap::plan(1);

$cache = new Store\KvpTTL;
$scope = 'floodcontrol/test/userid-' . microtime(TRUE) .'/';
$fc = new Store\FloodControl( $cache, array('scope'=>$scope, 'max'=>2 ) );
$actual = array();
for( $i = 0; $i < 3; $i++){
    $actual[] = $fc->go();
    Time::offset(1);
}
$expected = array(
    true,
    false,
    true,
);

Tap::is( $actual, $expected, 'first attempt works, second fails on short flood, last one fails on long flood control');
