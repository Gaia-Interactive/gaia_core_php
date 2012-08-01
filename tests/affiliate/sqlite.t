#!/usr/bin/env php
<?php
use Gaia\Affiliate;
use Gaia\DB;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_sqlite_installed.php';

try {
    $db = new DB\Except( new DB( new PDO( 'sqlite::memory:' ) ) );
} catch( \Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}

DB\Connection::load( array('test'=> function () use ( $db ) { return $db; }) );

$affiliate = new Affiliate\Sqlite('test', 'test');
$sql = $affiliate->initialize();

include __DIR__ . '/.base_test.php';
