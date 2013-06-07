<?php
use Gaia\Identifier;
use Gaia\DB;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

 
$db = new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306');

if( $db->connect_error ){
    Tap::plan('skip_all', 'mysqli: ' . $db->connect_error );
}

$create_identifier = function ($table=NULL) use ( $db ){
    if( $table === NULL ) $table = 'test_identifier';
    $db = new DB( $db );
    DB\Connection::add('test', $db );

    $identifier = new Identifier\MySQL(function() use( $db ){ return $db; }, $table );
    
    $schema = $identifier->schema();
    $schema = str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', $schema);
    $db->execute( $schema );
    
    return $identifier;
};

