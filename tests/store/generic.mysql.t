#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\DB;

DB\Connection::load( array(
    'test'=> function(){return new DB\Except(new Gaia\DB( new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306') ) );}
));




$check_table = function( $connstring, $table ) {
    static $tables;
    if( ! isset( $tables ) ) $tables = array();
    $check = $connstring . '.' . $table;
    if( isset( $tables[ $check ] ) ) return;
    $db =  DB\Connection::instance($connstring);
    $tables[ $check ] = 1;
    $rs = $db->execute('SHOW TABLES LIKE %s', $table);
    $row = $rs->fetch();
    if( $rs->fetch() ) return;
    $db->execute( Store\Mysql::initializeStatement( $table ) );
};


$resolver = function ( $key ) use ( $check_table ) {
    $connstring = 'test';
    $table = 'gaia_store_mysql_test_' . str_pad( abs(crc32($key)) % 2, 3, '0', STR_PAD_LEFT);
    $check_table( $connstring, $table );
    return array( $connstring, $table);
};

$cache = new Store\Mysql( $resolver );
$skip_expiration_tests = TRUE;
include __DIR__ . '/generic_tests.php';