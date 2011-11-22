#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/memcache_installed.php';
include __DIR__ . '/../assert/memcache_running.php';

$cache = new Store\Couchbase(array( 'app'=> 'dev_v' . time(), 'rest'=>'http://127.0.0.1:5984/default/', 'core'=>'127.0.0.1:11211'));
$cache->addServer('127.0.0.1', '11211');
$skip_expiration_tests = TRUE;
include __DIR__ . '/generic_tests.php';