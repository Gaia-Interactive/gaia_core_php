#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Http\Request;

if( ! function_exists('curl_init') ){
    Tap::plan('skip_all', 'php curl library not installed');
}

if( ! @fsockopen('127.0.0.1', 11299) ){
    Tap::plan('skip_all', 'http://127.0.0.1:11299/ not started. run ./tests/webservice/start.sh');
}

Tap::plan(4);

$request = new Request('http://@127.0.0.1:11299/restricted.html');
$response = $request->exec();
Tap::is( $response->http_code, '401', 'got back a 401 response');
Tap::like( $response->body, '/Unauthorized/i', 'body says Unauthorized');
$request = new Request('http://foo:bar@127.0.0.1:11299/http_auth.php');
$response = $request->exec(array(CURLAUTH_DIGEST=>1));
Tap::is( $response->http_code, '200', 'after entering username and password, got in successfully');
Tap::like( $response->body, '/hello foo/i', 'script echoed our username back to us');
