#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\Apn\WrapNotice as notice;
use Gaia\Apn\Message;

Tap::plan(11);



$r1 = new notice();

$r1->setDeviceToken( $token = randomDeviceToken() );
Tap::is( $r1->getDeviceToken(), $token, 'set and retrieved the device token');

$r1->setMessageId( $message_id = mt_rand(1, 10000000) );
Tap::is( $r1->getMessageId(), $message_id, 'set and retrieved the message id');


$r1->setExpires( $expires = time() + mt_rand(1, 10000000) );
Tap::is( $r1->getExpires(), $expires, 'set and retrieved the expiry time');


$message = new Message();
$message->setText('the number of the day is ' . mt_rand(1, 10000000));
$raw_message = $message->serialize();

$r1->setRawMessage($raw_message);
Tap::is( $r1->getRawMessage(), $raw_message, 'set and retrieved a raw message string');


$r1->app = 'test1';

$binary = $r1->serialize();

$r2 = new notice($binary);

Tap::is( print_r( $r1, TRUE), print_r($r2, TRUE), 'serialized the object then passed it to new object constructor to recreate it');

$message = new Message();

$message->setText( $raw_message );

$r1->setMessage( $message );

Tap::is( print_r( $r1->getMessage(), TRUE), print_R( $message, TRUE), 'setting the message as an object and grabbing it back out'); 


$err = NULL;
try {
    $r3 = new notice( 'blah 111' );
} catch( Exception $e ){
    $err = $e->getMessage();
}

Tap::like($err, '#too short#i', 'passing in a string that cant be unpacked triggers and exception');






$invalid_message = 'invalid message this is rediculous if this works, i dont think it will. testing just to see what unpack will do with it';

$err = NULL;
try {
    $r3 = new notice( pack('n', 0) . $invalid_message );
} catch( Exception $e ){
    $err = $e->getMessage();
}




Tap::like($err, '#invalid command#i', 'passing in an invalid string results in an exception');



$err = NULL;
try {
    $r3 = new notice( pack('n', 2) . '[]' . pack('C', 1) . $invalid_message );
} catch( Exception $e ){
    $err = $e->getMessage();
}

Tap::like($err, '#invalid message length#i', 'passing in an invalid string results in an exception');


$r4 = new notice();
$r4->setDeviceToken( $token );

$binary = $r4->serialize();

$r5 = new notice();
$r5->unserialize( $binary );


Tap::is( print_r( $r4, TRUE), print_r( $r5, TRUE), 'serialize/deserialize notice with no message');


$err = NULL;
try {
    $r6 = new notice();
    $r6->serialize();
} catch( Exception $e ){
    $err = $e->getMessage();
}

Tap::like($err, '#no device token#i', 'require a device token to serialize');


