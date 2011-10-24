#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Cookie();
include __DIR__ . '/generic_tests.php';