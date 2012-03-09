#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\Apn\notice;
use Gaia\Apn\Message;
use Gaia\APN\Connection;

Tap::plan(12);

$token = randomDeviceToken();

$conn = new Connection( fopen('php://temp', 'r+b') );
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

Tap::is( $conn->out,  $binary . $binary, 'added another message, both messages sitting in the out buffer');

Tap::ok( $conn->write(), 'successfully wrote the message to the stream');

Tap::is($conn->out, '', 'after writing, write buffer is empty');

Tap::is( $conn->in, '', 'didnt give us an error response');

//Tap::debug( $conn );

$res = $conn->send( $notice );

Tap::is($res, '', 'sent the message and read the response all in one pass');

rewind($conn->stream);

$data = fread($conn->stream, strlen( $binary )  * 3 );

Tap::is( $data, $binary . $binary . $binary, 'verified the data written is correct by peeking at the write stream data');

$conn->in = pack('CCN', '8', '1', $id = mt_rand(1, 10000000));

$errors = $conn->readResponses();

Tap::is( count( $errors ), 1, 'faked an error into the response');

$error = array_pop( $errors );

Tap::is( $error->status, 1, 'error status code is 1');

Tap::is( $conn->in, '', 'after reading the responses, the connection read buffer is empty');


