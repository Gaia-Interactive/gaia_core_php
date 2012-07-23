#!/usr/bin/env php
<?php
use Gaia\Stratum;
use Gaia\Test\Tap;
include __DIR__ . '/../common.php';
$stratum = new Stratum\Cache( new Stratum\Internal(), new Gaia\Container);
include __DIR__ .'/.basic_test_suite.php';
