#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

use Gaia\Test\Tap;
use Gaia\affiliate;
use Gaia\DB;

$db = new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306');

if( $db->connect_error ){
    Tap::plan('skip_all', 'mysqli: ' . $db->connect_error );
}

DB\Connection::load( array(
    'test'=>function() use( $db ){
        return new DB\Except( new DB( $db ));
    },
));

$affiliate = new affiliate\Mysql('test', 'test');
$sql = $affiliate->schema();
$sql = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $sql);
$rs = DB\Connection::instance('test')->execute( $sql );


include __DIR__ . '/.base_test.php';
