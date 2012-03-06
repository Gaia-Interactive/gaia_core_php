#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\Apn\Response;

Tap::plan(27);

foreach( Response::responseCodes() as $status => $message ){
    $response = new Response( pack('CCN', '8', $status, $id = mt_rand(1, 10000000)) );
    Tap::ok( $response instanceof Response, 'created new response object with status ' . $status );
    Tap::is( $response->status, $status, 'status set correctly');
    Tap::is( $response->message, $message, 'message set correctly');
}
