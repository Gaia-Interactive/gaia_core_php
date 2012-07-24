#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\DB;

$db = new DB\Except(new Gaia\DB( new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306') ) );
DB\Connection::load( array('test'=>function() use( $db ){ return $db; } ) );

$table = 'mysqlowner_loadtest';
$store = new Store\OwnerMySQL( mt_rand(1, 100000000), 'test', $table );
$schema = $store->schema();
$db->execute( $schema );

include __DIR__ . '/.load_test.php';
