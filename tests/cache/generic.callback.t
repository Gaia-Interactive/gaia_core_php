#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;
$cache = new Cache\Callback(new Cache\Mock(), array());
include __DIR__ . '/generic_tests.php';