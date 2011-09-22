#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Http\Request;

if( ! @fsockopen('github.com', 443) ){
    Tap::plan('skip_all', 'unable to connect to remote host for test');
}

Tap::plan(7);

$request = new Request('https://github.com/gaiaops/gaia_core_php');
$start = microtime(TRUE);
$response = $request->exec();
$elapsed = number_format( microtime(TRUE) - $start, 5);
Tap::is( $response->http_code, '200', 'got back a 200 response');
Tap::cmp_ok($response->size_download, '>', 0, 'got back content');
Tap::cmp_ok($response->speed_download, '>', 0, 'measured how long it took');
Tap::cmp_ok( $diff = number_format(abs( $elapsed - $response->total_time), 4), '<', 0.01, 'total_time measured matches expectations: '. $diff );
Tap::like( $response->request_header, '#GET /gaiaops/gaia_core_php HTTP/1.1#i', 'request header sent to the correct url');
Tap::like( $response->body, '/gaia_core_php hosted on GitHub/i', 'got back the correct content');
Tap::is( $response->headers->Connection, 'keep-alive', 'sent a keep-alive header and got back a keep-alive response');
Tap::is( strlen( $response->body ), $response->headers->{'Content-Length'}, 'got back expected amount of content');
unset( $response->body );
//print_r( $response );
