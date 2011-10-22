#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Signed(new Store\ContainerTTL(), 'my secret');
include __DIR__ . '/generic_tests.php';