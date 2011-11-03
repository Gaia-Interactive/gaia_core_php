#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;

$raw = file_get_contents(__DIR__ . '/UTF-8-test.txt');



if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}


Tap::plan(2);

Tap::cmp_ok( @json_encode( $raw ), '===', 'null', 'json unable to parse the raw doc');

$converted = UTF8::to($raw);
//print $converted;
Tap::todo_start();
Tap::cmp_ok( @json_encode( $converted ), '!==', 'null', 'json able to parse the converted doc');
Tap::todo_end();
