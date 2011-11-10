#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_mysql_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

$instance = function(){
    return new DB\Driver\PDO('mysql:host=127.0.0.1;dbname=test;port=3306' );
};

try {
    $db = $instance();
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}

include __DIR__ . '/injection.mysql.php';
