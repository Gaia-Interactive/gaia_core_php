#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Cache;
$cache = new Cache\Cookie();
include __DIR__ . '/generic_tests.php';
