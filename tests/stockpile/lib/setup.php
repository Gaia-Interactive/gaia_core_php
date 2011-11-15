<?php
require __DIR__ . '/../../common.php';
include __DIR__ . '/../../assert/bcmath_installed.php';
include __DIR__ . '/../../assert/date_configured.php';

include __DIR__ . '/functions.php';
Gaia\DB\Resolver::load( array('test0'=>'test', 'test1'=>'test') );

Gaia\Stockpile\Storage::attach( 
    function ( Gaia\Stockpile\Iface $stockpile, $name ){
        return Gaia\DB\Resolver::get('test' . abs(crc32( $stockpile->user() ) ) % 2 );
    } 
);

Gaia\Stockpile\Storage::enableAutoSchema();


if( strpos(php_sapi_name(), 'apache') !== FALSE ) print "\n<pre>\n";

$app = 'test1';
