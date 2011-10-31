<?php
require __DIR__ . '/../lib/setup.php';
use Gaia\DB;
use Gaia\Test\Tap;

if( ! class_exists('\MySQLi') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}


if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}


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