#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;

$raw = file_get_contents(__DIR__ . '/../sample/UTF-8-test.txt');

/*
$ch = curl_init("http://www.cl.cam.ac.uk/~mgk25/ucs/examples/UTF-8-test.txt");
curl_setopt_array($ch, array(
CURLOPT_RETURNTRANSFER => 1,
CURLOPT_ENCODING => 'UTF-8',
));
$raw = curl_exec($ch);
file_put_contents(__DIR__ . '/../sample/UTF-8-test.txt', $raw);
*/


if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}
$problems = array();
$lines = explode("\n", $raw);
foreach($lines as $i=>$line ){
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
    $newline = @json_decode(json_encode( UTF8::to($line) ));
    Tap::ok( $newline !== NULL , "fixed broken line ".  ($i + 1) . ' :' . trim($newline));
}
Tap::todo_end();
