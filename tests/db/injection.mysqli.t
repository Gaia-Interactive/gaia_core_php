#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

$instance = function(){
    return new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306');
};
$db = $instance();
if( $db->connect_error ){
    Tap::plan('skip_all', 'mysqli: ' . $db->connect_error );
}

include __DIR__ . '/injection.mysql.php';
