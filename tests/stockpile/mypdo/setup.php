<?php
require __DIR__ . '/../lib/setup.php';
use Gaia\Test\Tap;

if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}

Gaia\DB\Connection::load( array('test'=>function () {
    return new Gaia\DB\Driver\PDO('mysql:host=127.0.0.1;dbname=test;port=3306');
}
) );