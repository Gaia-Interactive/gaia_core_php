#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\NewID;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/bcmath_installed.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_mysql_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

try {
    $db = new \PDO('mysql:host=127.0.0.1;dbname=test;port=3306');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString() );
}

include __DIR__ . '/db.test.php';