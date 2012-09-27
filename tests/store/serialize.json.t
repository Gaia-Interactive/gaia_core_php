#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Serialize( new Store\KVP(), new \Gaia\Serialize\JSON(''));
include __DIR__ . '/generic_tests.php';