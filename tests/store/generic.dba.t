#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;
if( ! function_exists('dba_open') ) Tap::plan('skip_all', 'dba not enabled');
$file = tempnam('/tmp', 'PHP');
$cache = new Store\DBA($handle =  dba_open( $file, 'cd' ));
#$cache = new Store\DBA($file); // this works too

include __DIR__ . '/generic_tests.php';
/*
$key = dba_firstkey( $handle );
do {
    print "\n$key: " . dba_fetch($key, $handle );
} while( $key = dba_nextkey( $handle ) );
*/
if( $file ) unlink( $file );
