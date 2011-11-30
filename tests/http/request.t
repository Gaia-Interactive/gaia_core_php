#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Http\Request;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/webservice_started.php';

Tap::plan(14);

$request = new Request('http://127.0.0.1:11299/http_json_echo.php');
$response = $request->exec();
Tap::is( $response->http_code, 200, 'got back a 200 ok response');
Tap::is( trim($response->body), '[]', 'got back an empty json array');
$request = new Request('http://127.0.0.1:11299/http_json_echo.php?test=1');
$response = $request->exec();
Tap::is( trim($response->body), '{"test":"1"}', 'sent a GET param and got it echoed back');
$request = new Request('http://127.0.0.1:11299/http_json_echo.php');
$request->post = array('test'=>1);
$response = $request->exec();
Tap::is( trim($response->body), '{"test":"1"}', 'sent a POST param and got it echoed back');

$request = new Request('http://127.0.0.1:11299/http_json_echo.php');
$request->post = '<?xml><test>1</test>';
$response = $request->exec();
Tap::is( trim($response->body), '{"__raw__":"<?xml><test>1<\/test>"}', 'sent POST raw xml and got it echoed back');
Tap::like( $response->raw, '/content-type\: text\/xml/i', 'request header content-type is xml');

$request = new Request('http://127.0.0.1:11299/http_json_echo.php');
$request->post = array('test'=>1);
$request->method = 'PUT';
$response = $request->exec();
Tap::like( $response->raw, '#PUT \/http_json_echo\.php#i', 'successfully sent a PUT request');
Tap::is( $response->headers->{'X-Request-Method'}, 'PUT', 'response header shows the PUT request came through');
Tap::is( trim($response->body), '{"__raw__":"test=1"}', 'post data echoed back as raw');


$request = new Request('http://127.0.0.1:11299/http_json_echo.php?test=1');
$request->method = 'DELETE';
$response = $request->exec();

Tap::like( $response->raw, '#DELETE \/http_json_echo\.php#i', 'successfully sent a DELETE request');
Tap::is( $response->headers->{'X-Request-Method'}, 'DELETE', 'response header shows the DELETE request came through');


$ch = curl_init($url = 'http://127.0.0.1:11299/http_json_echo.php?test=1');

$request = new Request($ch);

Tap::cmp_ok( $request->handle, '===', $ch, 'passed in a curl handle to constructor ... set up as the request handle');

Tap::cmp_ok( $request->url, '===', $url, 'url extracted out of the curl handle and set in as the request url');

$response = $request->exec();
Tap::is( trim($response->body), '{"test":"1"}', 'sent request with the request constructed from the curl handle and got expected result');


//Tap::debug( $response );
//print_r( $response );