#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;

$raw = file_get_contents(__DIR__ . '/UTF-8-test.txt');



if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}
$problems = array();
foreach(explode("\n", $raw) as $i=>$line ){
    if( substr($line, -1) != '|' ) continue;
    $line = substr( $line, 0, -1);
    if( @json_encode( $line ) !== 'null' ) continue;
    $problems[$i] = $line;
}

$ct = count( $problems);

if( ! $ct ){
    Tap::plan('skip_all', 'no problems found');
}

Tap::plan( $ct );

Tap::todo_start();
foreach( $problems as $i=>$line ){
    $res = @json_encode( UTF8::to($line) ) !== 'null' ? TRUE : FALSE;
    Tap::ok( $res , "fixed broken line ".  ($i + 1) . ' :' . trim($line));
}
Tap::todo_end();
