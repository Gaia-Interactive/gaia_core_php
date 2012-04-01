#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\DB;

$db = new DB\Except(new Gaia\DB( new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306') ) );
$table = 'gaia_store_mysql_test';
$cache = new Store\Mysql( $db, $table );
$cache->initialize();

include __DIR__ . '/generic_tests.php';