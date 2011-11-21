#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/memcache_installed.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/couchbase_running.php';

Tap::plan(1);

$cache = new Store\Prefix(new Store\Couchbase(), md5( __FILE__ . '/' . microtime(TRUE) . '/' . php_uname()));
$cache->addServer('127.0.0.1', '11211');
$res = $cache->query('dev_v1', 'test', array('limit'=>5));

Tap::ok( is_array( $res ), 'query returned an array');

Tap::debug($res);