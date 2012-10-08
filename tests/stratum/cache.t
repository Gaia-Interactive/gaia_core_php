#!/usr/bin/env php
<?php
use Gaia\Stratum;
use Gaia\Test\Tap;
include __DIR__ . '/../common.php';
$plan = 1;
$stratum = new Stratum\Cache( new Stratum\Internal(), new Gaia\Container);
include __DIR__ .'/.basic_test_suite.php';

$stratum = new Stratum\Cache( new Stratum\Internal(), new Gaia\Container);
$data = $stratum->query();
$stratum->store($key = 'foo' . mt_rand(1, 100), $value = mt_rand(1, 100));
$res = $stratum->query();
Tap::is( $res, array($key => $value ), 'cache is refreshed after new value is stored');
