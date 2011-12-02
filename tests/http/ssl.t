#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Http\Request;
use Gaia\Http\Util;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';


if( ! @fsockopen('verisign.com', 443) ){
    Tap::plan('skip_all', 'unable to connect to verisign.com for ssl test');
}

Tap::plan(10);

$request = new Request('https://www.verisign.com/');
$start = microtime(TRUE);
$response = $request->send(
    array(
    CURLOPT_CONNECTTIMEOUT=>5, 
    CURLOPT_TIMEOUT=>10, 
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HTTPHEADER => array('Connection: Keep-Alive','Keep-Alive: 300')));
$elapsed = number_format( microtime(TRUE) - $start, 5);
Tap::is( $response->http_code, '200', 'got back a 200 response on an SSL request');
Tap::cmp_ok($response->size_download, '>', 0, 'got back content');
Tap::cmp_ok($response->speed_download, '>', 0, 'measured how long it took');
Tap::cmp_ok( $diff = number_format(abs( $elapsed - $response->total_time), 4), '<', 0.01, 'total_time measured matches expectations: '. $diff );
Tap::like( $response->request_header, '#GET / HTTP/1.1#i', 'request header sent to the correct url');
Tap::like( $response->request_header, '#Connection: Keep-Alive#i', 'found my connection keep-alive header in my request');
Tap::like( $response->request_header, '#Keep-Alive: 300#i', 'found my keep-alive timeout header in my request');
Tap::like( $response->body, '/VeriSign Authentication Services/i', 'got back the correct content');
$headers = Util::parseHeaders( $response->response_header );
Tap::is( strtolower($headers['Connection']), 'keep-alive', 'sent a keep-alive header and got back a keep-alive response');
Tap::cmp_ok( strlen( $response->body ), '>', 1000, 'got back expected amount of content');
//unset( $response->body );
//print_r( $response );
