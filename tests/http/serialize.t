#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Http\Request;
use Gaia\Serialize;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/webservice_started.php';

Tap::plan(6);

$request = new Request('http://127.0.0.1:11299/http_serialize.php');
$request->post = array('test'=>1);
$request->serializer = new Serialize\Base64;
$response = $request->exec();
Tap::like( $response->raw, '#x-serialize\: base64#i', 'sent serialization type base64 in headers');
Tap::cmp_ok( $response->body, '===', $request->post, 'serialized the data and got the data back deserialized');

$request = new Request('http://127.0.0.1:11299/http_serialize.php');
$request->post = array('test'=>1);
$request->serializer = new Serialize\Json;
$response = $request->exec();
Tap::like( $response->raw, '#x-serialize\: json#i', 'sent serialization type json in headers');
Tap::cmp_ok( $response->body, '===', $request->post, 'serialized the data and got the data back deserialized');


$request = new Request('http://127.0.0.1:11299/http_serialize.php');
$request->post = array('test'=>1);
$request->serializer = new Serialize\PHP;
$response = $request->exec();
Tap::like( $response->raw, '#x-serialize\: php#i', 'sent serialization type php in headers');
Tap::cmp_ok( $response->body, '===', $request->post, 'serialized the data and got the data back deserialized');


//print_r( $response );

