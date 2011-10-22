#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
$cache = new Store\Prefix(new Store\Memcache(), md5( __FILE__ . '/' . microtime(TRUE) . '/' . php_uname()));
if( ! fsockopen('127.0.0.1', '11211') ){
    Tap::plan('skip_all', 'memcache not running on 127.0.0.1:11211');
}
$cache->addServer('127.0.0.1', '11211');
$skip_expiration_tests = TRUE;
include __DIR__ . '/generic_tests.php';