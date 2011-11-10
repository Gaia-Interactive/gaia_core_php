<?php
use Gaia\Test\Tap;

include __DIR__ . '/../lib/setup.php';
include __DIR__ . '/../../assert/pdo_installed.php';
include __DIR__ . '/../../assert/pdo_mysql_installed.php';
include __DIR__ . '/../../assert/mysql_running.php';

Gaia\DB\Connection::load( array('test'=>function () {
    return new Gaia\DB( new \PDO('mysql:host=127.0.0.1;dbname=test;port=3306'));
}
) );