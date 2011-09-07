#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DB;



try {
    DB\Connection::load( array(
        'test'=> function(){
             $db = new DB\Driver\MySQLi( 
                $host = '127.0.0.1', 
                $user = NULL, 
                $pass = NULL, 
                $db = 'test', 
                '3306');
                return $db;
        }
    ));
    $db = DB\Connection::instance('test');
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}

//print_r( $db );

$table = 'trantest_' . str_replace('.', '_',  microtime(TRUE));


$rs = DB\Connection::instance('test')->query("CREATE TABLE $table( id int unsigned NOT NULL PRIMARY KEY ) ENGINE=INNODB");
if( ! $rs ) {
    Tap::plan('skip_all', 'unable to create table');
}
Tap::plan(4);

$db = DB\Transaction::instance('test');
$db->query("INSERT into $table (id) VALUES(1)");
$rs = $db->query("SELECT id from $table WHERE id = 1");
Tap::is( $rs->fetch_assoc(), array('id'=>1), 'inserted a new row and selected it back');

DB\Transaction::rollback();

$rs = DB\Connection::instance('test')->query("SELECT id from $table WHERE id = 1");
Tap::is( $rs->fetch_assoc(), NULL, 'after rollback, no row found');


$db = DB\Transaction::instance('test');
$db->query("INSERT into $table (id) VALUES(2)");
$rs = $db->query("SELECT id from $table WHERE id = 2");
Tap::is( $rs->fetch_assoc(), array('id'=>2), 'inserted a new row and selected it back');
DB\Transaction::commit();
$rs = DB\Connection::instance('test')->query("SELECT id from $table WHERE id = 2");
Tap::is( $rs->fetch_assoc(), array('id'=>2), 'after commiting can still select the row');

DB\Connection::instance('test')->query("DROP TABLE IF EXISTS $table");