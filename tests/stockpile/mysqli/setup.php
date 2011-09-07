<?php
require __DIR__ . '/../lib/setup.php';
use Gaia\DB;
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