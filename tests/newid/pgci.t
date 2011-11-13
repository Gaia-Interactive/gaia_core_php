#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\NewID;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/bcmath_installed.php';
include __DIR__ . '/../assert/ci_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

$db = DB( array(
        'dbdriver'	=> 'postgre',
        'hostname'	=> '127.0.0.1',
        'port'      => '5432',
        'database'	=> 'test') );

include __DIR__ . '/db.test.php';