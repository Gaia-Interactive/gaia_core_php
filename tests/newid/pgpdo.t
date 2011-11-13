#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\NewID;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/bcmath_installed.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_pgsql_installed.php';
include __DIR__ . '/../assert/postgres_running.php';

try {
    $db = new PDO('pgsql:host=127.0.0.1;dbname=test;port=5432');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString() );
}

include __DIR__ . '/db.test.php';