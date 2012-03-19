#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mbstring_installed.php';

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

$broken_lines = array(75, 76, 83, 84, 93, 102, 103, 105, 106, 107, 108, 109, 110, 114, 115, 116, 117, 175, 176, 177);

foreach( $problems as $i=>$line ){
    $newline = @json_decode(json_encode( UTF8::to($line) ));
    $line_no = $i + 1;
    $todo = FALSE;
    if( in_array( $line_no, $broken_lines ) ) $todo = TRUE;
    if( $todo ) Tap::todo_start();
    Tap::ok( $newline !== NULL , "fixed broken line ".  $line_no . ' :' . trim($newline));
    if( $todo ) Tap::todo_end();
}
