<?php
require __DIR__ . '/../lib/setup.php';
use Gaia\DB;
use Gaia\Test\Tap;

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}

DB\Connection::load(array('test'=>function () {
        return new DB\Driver\PDO( 'sqlite:/tmp/stockpile.db');
    }
));
