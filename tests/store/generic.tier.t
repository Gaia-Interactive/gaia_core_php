#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Tier(new Store\ContainerTTL(),new Store\ContainerTTL());
include __DIR__ . '/generic_tests.php';