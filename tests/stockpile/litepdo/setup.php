<?php
require __DIR__ . '/../lib/setup.php';
use Gaia\DB;
use Gaia\Test\Tap;

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}

DB\Connection::load(array('test'=>function () {
        $db = new DB\Driver\PDO( 'sqlite:/tmp/stockpile.db');
        $db->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Gaia\DB\Driver\PDOStatement', array($db)));
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT );
        return $db;
    }
));
