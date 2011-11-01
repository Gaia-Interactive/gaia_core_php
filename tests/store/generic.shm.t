#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
if( ! function_exists('shm_attach') ) Tap::plan('skip_all', 'shm not enabled');
$cache = new Store\Shm(tempnam('/tmp', 'PHP'));
include __DIR__ . '/generic_tests.php';