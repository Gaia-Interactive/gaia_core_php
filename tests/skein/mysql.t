#!/usr/bin/env php
<?php
use Gaia\Skein;
use Gaia\Container;
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

$thread = bcsub( time(), 1000000000 ) . str_pad( mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    
$db = new Gaia\DB( new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306'));

if( $db->connect_error ){
    Tap::plan('skip_all', 'mysqli: ' . $db->connect_error );
}

$cache = new Container;

$callback = function( $table ) use ( $db, $cache ){
    if( ! $cache->add( $table, 1, 60 ) ) return $db;
    $sql = (substr($table, -5) == 'index') ? 
    Skein\MySQL::indexSchema( $table ) : Skein\MySQL::dataSchema( $table );
    $sql = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $sql );
    $db->execute( $sql );
    return $db;
};



$skein = new Skein\MySQL( $thread, $callback, 'test' );

DB\Transaction::start();

$extra_tests = 1;

include __DIR__ . '/.basic_test_suite.php';

Tap::ok( DB\Transaction::rollback(), 'rolling back all the queries');

//Tap::debug( $store->all() );