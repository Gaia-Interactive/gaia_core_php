#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Serialize( $core = new Store\KVP(), $s = new \Gaia\Serialize\QueryString());
include __DIR__ . '/generic_tests.php';
Tap::debug( $core );