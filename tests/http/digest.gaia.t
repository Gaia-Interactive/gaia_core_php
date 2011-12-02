#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Http\Request;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/webservice_started.php';

Tap::plan(6);

$request = new Request('http://127.0.0.1:11299/http_auth_digest.php');
$response = $request->send();
Tap::is( $response->http_code, '401', 'got back a 401 response');
Tap::like( $response->body, '/Unauthorized/i', 'body says Unauthorized');
$request = new Request('http://foo:bar@127.0.0.1:11299/http_auth_digest.php');
$response = $request->send(array(CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST));
Tap::is( $response->http_code, '200', 'after entering username and password, got in successfully');
Tap::like( $response->body, '/all ur base r belong 2 us/i', 'script gave back proper response');


$request = new Request('http://bazz:quux@127.0.0.1:11299/http_auth_digest.php');
$response = $request->send(array(CURLOPT_HTTPAUTH=>CURLAUTH_DIGEST));
Tap::is( $response->http_code, '200', 'after entering username and password that were stored in clear text, got in successfully');
Tap::like( $response->body, '/all ur base r belong 2 us/i', 'script gave back proper response');
