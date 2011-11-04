#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;

$raw = file_get_contents(__DIR__ . '/../sample/i_can_eat_glass.txt');



if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}


Tap::plan(152);
Tap::cmp_ok( json_decode(json_encode($raw)), '===', $raw, 'i can eat glass files is correctly encoded');
foreach(explode("\n", $raw) as $i=>$line ){
    $newline = UTF8::to($line);
    Tap::ok( $newline == $line , "didnt change encoding of line ".  ($i + 1) . ' :' . trim($line));
}
