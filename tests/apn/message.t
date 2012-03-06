#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Apn\Message;

Tap::plan(14);
$text = 'hello world, the random number is ' . mt_rand(1, 5000);
$badge = mt_rand(1, 100000);
$sound = 'pong.wav';
$foo = 'bar';

$payload = array('aps'=>array('alert'=>$text, 'badge'=>$badge, 'sound'=>$sound), 'foo'=>$foo);
$m = new Message();

$m->setText($text);
Tap::is( $m->getText(), $text, 'wrote the text into the message and got it back');

$m->setBadge( $badge );
Tap::is( $m->getBadge(), $badge, 'wrote the badge into the message and got it back');


$m->setSound( $sound );
Tap::is( $m->getSound(), $sound, 'wrote the sound into the message and got it back');

$m->setCustomProperty('foo', $foo );
Tap::is( $m->getCustomProperty('foo'), $foo, 'wrote a custom property into the message and got it back');

Tap::is( $json = $m->serialize(), json_encode( $payload ), 'created a message and serialized it');

$m2 = new Message( $json );
Tap::is( $m2->serialize(), $json, 'passing the json string to the message constructor populates the object');
$m3 = new Message();

Tap::is( $m3->serialize(), '{"aps":{}}', 'empty object creates a stub entry of json');

$m3->unserialize( $json );

Tap::is( print_r( $m3, TRUE), print_r( $m, TRUE), 'after unserializing the json payload from earlier, both objects match up');

$longtext = '';

$len = Message::MAXIMUM_SIZE - (strlen( $json ) - strlen($text));

for( $i = 0; $i<$len; $i++) $longtext .= 'a';

$m->setText( $longtext );

Tap::is($m->getText(), $longtext, 'wrote longest string possible into message without overflowing.)');

$payload['aps']['alert'] = $longtext;

Tap::is( $json = $m->serialize(), json_encode( $payload ), 'serialized a message with long text');


$longtext .= 'aasdsdfsdf';

$m->setText( $longtext );

Tap::is( $json = $m->serialize(), json_encode( $payload ), 'added to the serialized text, got auto-truncated');

$m->setAutoAdjustLongPayload(FALSE);

$m->setText( $longtext );

$e = null;
try {
    $m->serialize();
} catch( Exception $e ){
    
}

Tap::ok( $e instanceof \Exception, 'an exception was thrown when auto adjust variable not set');

$alert = array('body'=>'hello world', 'action-loc-key'=>'play', 'loc-key'=>'test1', 'loc-args'=>array(), 'launch-image'=>'img1.png');

$payload['aps']['alert'] = $alert;

$m->setAlert( $alert );

Tap::is( $json = $m->serialize(), json_encode( $payload ), 'serialize a message with a more complex alert structure');

$alert['invalid-field'] = 'test';
$e = null;
try {
    $m->setAlert($alert);
} catch( Exception $e ){
    
}

Tap::ok( $e instanceof \Exception, 'an exception was thrown when invalid alert body set');


