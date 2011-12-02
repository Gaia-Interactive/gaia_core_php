#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Http\Request;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/webservice_started.php';

Tap::plan(4);

$request = new Request('http://127.0.0.1:11299/bigdoc.php?size=100&iterations=500&usleep=100');

$buf = '';
$write_ct = 0;

$writer = function( $ch, $data ) use( & $buf, & $write_ct){
    $buf .= $data;
    $write_ct++;
    return strlen( $data );
};

$request->build = function( $request, array & $opts ) use( $writer ) {
    $opts[ CURLOPT_WRITEFUNCTION ] = $writer;
};

$res = $request->send();
$len = strlen( $buf );

Tap::cmp_ok( $len, '>', 10000, "subverted the writing handler, got back a block of text: $len chars");
Tap::cmp_ok($write_ct, '>', 10, "my write function callback was triggered more than 10 times: $write_ct");

$buf = '';
$write_ct = 0;

$request = new Request('http://127.0.0.1:11299/bigdoc.php?size=100&iterations=500&usleep=100');
print_r( $request );
$res = $request->send(array(CURLOPT_WRITEFUNCTION =>$writer));
$len = strlen( $buf );

Tap::cmp_ok( $len, '>', 10000, "passed the CURLOPT_WRITEFUNCTION to exec , got back a block of text: $len chars");
Tap::cmp_ok($write_ct, '>', 10, "my write function callback was triggered more than 10 times: $write_ct");