#!/usr/bin/env php
<?php
use Gaia\Skein;
use Gaia\Container;
use Gaia\DB;

include __DIR__ . '/../common.php';

$thread = 5184;

$cache = new Container;

DB\Connection::load( array(
    'test'=>function(){ return new DB\Except( new Gaia\DB( new MySQLi( '127.0.0.1', NULL, NULL, 'test', '3306') )); },
));


$callback = function( $table ) use ( $cache ){
    $db = DB\Connection::instance('test');
    if( ! $cache->add( $table, 1, 60 ) ) return $db;
    $sql = (substr($table, -5) == 'index') ? 
    Skein\MySQL::indexSchema( $table ) : Skein\MySQL::dataSchema( $table );
    $db->execute( $sql );
    return $db;
};

$skein = new Skein\MySQL( $thread, $callback, $app_prefix = 'test002' );



$data = 'the time is ' . date('Y/m/d H:i:s');

$id = $skein->add( $data );

print "\nID: $id \n";

$data = $skein->get( $id );

print "\nDATA: " . print_r( $data, TRUE ) . "\n";

$skein->store( $id, array('foo'=>$data) );

$data = $skein->get( $id );

print "\nDATA: " . print_r( $data, TRUE ) . "\n";


$data = $skein->get( $skein->descending() );

print "\nDESCENDING ORDER: " . print_r( $data, TRUE ) . "\n";



$ct = 0;
$cb = function( $id, $data ) use( & $ct ){
    $ct++;
};

$skein->filterAscending($cb);

print "\nCOUNT: " . $ct . "\n";


print "\n";

