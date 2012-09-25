#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/apc_installed.php';

use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Apc();
$skip_expiration_tests = TRUE;
include __DIR__ . '/generic_tests.php';