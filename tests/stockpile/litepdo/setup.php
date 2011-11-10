<?php
use Gaia\DB;
use Gaia\Test\Tap;

include __DIR__ . '/../lib/setup.php';
include __DIR__ . '/../../assert/pdo_installed.php';
include __DIR__ . '/../../assert/pdo_sqlite_installed.php';

DB\Connection::load(array('test'=>function () {
        return new DB( new \PDO( 'sqlite:/tmp/stockpile.db'));
    }
));
