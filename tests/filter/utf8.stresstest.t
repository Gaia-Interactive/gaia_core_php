#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;

$raw = file_get_contents(__DIR__ . '/UTF-8-test.txt');



if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}


Tap::plan(209);

Tap::todo_start();
foreach(explode("\n", $raw) as $i=>$line ){
    //print $line . "\n";
    if( substr($line, -1) != '|' ) continue;
    $res = @json_encode( UTF8::to($line) ) !== 'null' ? TRUE : FALSE;
    Tap::ok( $res , "json able to parse converted line ".  ($i + 1));
    if( ! $res ) Tap::debug("failed on text: $line");
}
Tap::todo_end();
