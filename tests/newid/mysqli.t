#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\NewID;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/bcmath_installed.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

$db = new MySQLi( 
                $host = '127.0.0.1', 
                $user = NULL, 
                $pass = NULL, 
                $dbname = 'test', 
                '3306');
if( $db->connect_error ) Tap::plan('skip_all', $db->connect_error);

include __DIR__ . '/db.test.php';