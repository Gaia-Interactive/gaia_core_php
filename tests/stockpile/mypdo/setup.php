<?php
require __DIR__ . '/../lib/setup.php';
use Gaia\Test\Tap;

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}

if( ! in_array( 'mysql', PDO::getAvailableDrivers()) ){
    Tap::plan('skip_all', 'this version of PDO does not support mysql');
}

if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}

Gaia\DB\Connection::load( array('test'=>function () {
    return new Gaia\DB\Driver\PDO('mysql:host=127.0.0.1;dbname=test;port=3306');
}
) );