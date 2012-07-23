#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\DB;

$db = new DB\Except(new Gaia\DB( new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306') ) );
DB\Connection::load( array('test'=>function() use( $db ){ return $db; } ) );

$table = 'gaia_store_mysqlowner_test';
$cache = new Store\OwnerMySQL( mt_rand(1, 100000000), 'test', $table );
$schema = $cache->schema();
$schema = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $schema );
$db->execute( $schema );

include __DIR__ . '/generic_tests.php';