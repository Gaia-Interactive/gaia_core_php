#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysql_running.php';
include __DIR__ . '/../assert/ci_installed.php';

$instance = function(){
    return DB( array( 'dbdriver' => 'mysql', 'hostname' => '127.0.0.1', 'database' => 'test') );
};
$db = $instance();

include __DIR__ . '/injection.mysql.php';
