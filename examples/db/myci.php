<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../../tests/assert/ci_installed.php';
include __DIR__ . '/../../tests/assert/mysql_running.php';

try{
    $db =  DB( array(
            'dbdriver'	=> 'mysql',
            'hostname'	=> '127.0.0.1',
            'database'	=> 'test') );
} catch( Exception $e ){
    print("\n" . 'unable to connect' . "\n");
}

include __DIR__ . '/_mysql.php';
