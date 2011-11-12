<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../../tests/assert/mysqli_installed.php';
include __DIR__ . '/../../tests/assert/mysql_running.php';


$db = new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306');
if( $db->connect_error ){
    print("\n" . 'unable to connect' . "\n");
}

include __DIR__ . '/_mysql.php';
