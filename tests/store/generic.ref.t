#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$a = array();
$cache = new Store\Ref($a);
include __DIR__ . '/generic_tests.php';
