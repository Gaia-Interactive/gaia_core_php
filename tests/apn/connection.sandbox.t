#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\Apn\notice;
use Gaia\Apn\Message;
use Gaia\APN\Connection;

if( ! file_exists( $ssl_cert = __DIR__ . '/.cert.development.pem' ) ){
    Tap::plan('skip_all', 'No cert found');

}
$tokens = array();
foreach( explode("\n", @file_get_contents(__DIR__ . '/.tokens.development.txt')) as $token ){
    $token = trim($token);
    if( ! $token ) continue;
    $tokens[] = $token;
}


if( count( $tokens ) < 1 ) {
    Tap::plan('skip_all', 'No tokens found');
}

$token = $tokens[ array_rand( $tokens ) ];


Tap::plan(8);


$conn = new Connection\Sandbox( $ssl_cert );
Tap::ok( $conn instanceof Connection, 'created a new connection');

Tap::ok( is_resource( $conn->stream ), 'stream handle is a valid resource');



$notice = new Notice();
$notice->setDeviceToken( $token );
$notice->setMessageId( time() );
$notice->getMessage()->setText($text = 'Time: ' . date(DateTime::RFC1036));


$conn->add( $notice );

$binary = $notice->serialize();

Tap::is( $conn->out, $binary, 'add the message to the connection write queue');

$conn->add( $notice );

Tap::is( $conn->out,  $binary . $binary, 'added another message, both messages sitting in the out queue');

Tap::ok( $conn->write(), 'successfully wrote the message to the stream');

Tap::is($conn->out, '', 'after writing, write queue is empty');

Tap::is( $conn->in, '', 'apple didnt give us an error response');

//Tap::debug( $conn );

$res = $conn->send( $notice );

Tap::is($res, '', 'sent the message and read the response all in one pass');