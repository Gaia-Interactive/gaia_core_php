#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Http\Request;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/webservice_started.php';

Tap::plan(4);

$request = new Request('http://127.0.0.1:11299/http_auth.php');
$response = $request->exec();
Tap::is( $response->http_code, '401', 'got back a 401 response');
Tap::like( $response->body, '/no auth/i', 'body says no auth');
$request = new Request('http://foo:bar@127.0.0.1:11299/http_auth.php');
$response = $request->exec();
Tap::is( $response->http_code, '200', 'after entering username and password, got in successfully');
Tap::like( $response->body, '/hello foo/i', 'script echoed our username back to us');
