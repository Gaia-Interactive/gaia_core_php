#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Stat( new Store\KVP(), new Store\KVP);
include __DIR__ . '/generic_tests.php';