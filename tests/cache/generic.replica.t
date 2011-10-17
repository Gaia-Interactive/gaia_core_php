#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;
$cache = new Cache\Replica(array(new Cache\Prefix(new Cache\Mock(), '#a/'), new Cache\Prefix(new Cache\Mock(), '#b/')));
include __DIR__ . '/generic_tests.php';