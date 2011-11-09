<?php
use Gaia\DB;
use Gaia\Test\Tap;

include __DIR__ . '/../lib/setup.php';
include __DIR__ . '/../../assert/mysqli_installed.php';
include __DIR__ . '/../../assert/mysql_running.php';

DB\Connection::load( array(
    'test'=> function(){
         $db = new DB\Driver\MySQLi( 
            $host = '127.0.0.1', 
            $user = NULL, 
            $pass = NULL, 
            $db = 'test', 
            '3306');
            return $db;
    }
));