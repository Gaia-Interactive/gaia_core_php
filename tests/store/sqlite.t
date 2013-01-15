#!/usr/bin/env php
<?php

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_sqlite_installed.php';
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\DB;

try {    
    $db = new DB\Except(new Gaia\DB( new PDO( 'sqlite::memory:') ) );
    DB\Connection::load( array('test'=>function() use( $db ){ return $db; } ) );

} catch( \Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}

DB\Connection::load( array('test'=> function () use ( $db ) { return $db; }) );

$table = 'gaia_store_sqlite_test';
$cache = new Store\SQLite('test', $table );
$cache->initialize();

include __DIR__ . '/generic_tests.php';