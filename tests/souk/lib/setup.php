<?php
require __DIR__ . '/../../common.php';
include __DIR__ . '/../../assert/date_configured.php';

include __DIR__ . '/functions.php';
Gaia\DB\Resolver::load( array('test0'=>'test', 'test1'=>'test') );

Gaia\Souk\Storage::attach( 
    function ( Gaia\Souk\Iface $souk ){
        return Gaia\DB\Resolver::get('test' . abs(crc32( $souk->app() ) ) % 2 );
    } 
);

Gaia\Souk\Storage::enableAutoSchema();


if( strpos(php_sapi_name(), 'apache') !== FALSE ) print "\n<pre>\n";

$app = 'test1';
$expected_test_count = 68;