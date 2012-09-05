#!/usr/bin/env php
<?php
use Gaia\Stratum;
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

$stratum = new Stratum\OwnerSQLite( mt_rand(1, 100000000), 'test', 'test_owner_stratum' );
$stratum->init();

include __DIR__ .'/.basic_test_suite.php';

