#!/usr/bin/env php
<?php
use Gaia\Stratum;
use Gaia\DB;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

 
$db = new DB( new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306'));

if( $db->connect_error ){
    Tap::plan('skip_all', 'mysqli: ' . $db->connect_error );
}

DB\Connection::add('test', $db );

$stratum = new Stratum\IntMySQL('test', 'test_int_stratum' );
$schema = $stratum->schema();
$schema = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $schema);
$db->execute( $schema );

$use_int_keys = TRUE;

include __DIR__ .'/.basic_test_suite.php';

