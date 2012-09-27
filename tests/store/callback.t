#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Callback(new Store\KvpTTL(), array());
include __DIR__ . '/generic_tests.php';