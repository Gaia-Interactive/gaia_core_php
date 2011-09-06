<?php
require __DIR__ . '/../../common.php';
include __DIR__ . '/functions.php';
Gaia\DB\Connection::load(array('test'=>'litepdo:///tmp/stockpile.db'));
Gaia\DB\Resolver::load( array('test0'=>'test', 'test1'=>'test') );

Gaia\Stockpile\ConnectionResolver::attach( 
    function ( Gaia\Stockpile\Iface $obj ){
        return Gaia\DB\Resolver::get('test' . abs(crc32( $obj->user() ) ) % 2 );
    } 
);

if( strpos(php_sapi_name(), 'apache') !== FALSE ) print "\n<pre>\n";

$app = 'test1';
