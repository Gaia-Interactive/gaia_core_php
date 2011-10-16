#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;
$cache = new Cache\Tier(new Cache\Prefix(new Cache\Mock(), 'core/'), new Cache\Prefix(new Cache\Mock(), 'tier1/'));
include __DIR__ . '/generic_tests.php';