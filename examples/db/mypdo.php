<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../../tests/assert/pdo_installed.php';
include __DIR__ . '/../../tests/assert/pdo_mysql_installed.php';
include __DIR__ . '/../../tests/assert/mysql_running.php';


try{
    $db = new PDO('mysql:host=127.0.0.1;dbname=test;port=3306');
} catch( Exception $e ){
    print("\n" . 'unable to connect' . "\n");
}

include __DIR__ . '/_mysql.php';
