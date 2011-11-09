#!/usr/bin/env php
<?php
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/webservice_started.php';

Tap::plan(9);

$ch = curl_init("http://127.0.0.1:11299/shortcircuit.php/test/");
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
$res = trim(curl_exec($ch));
$info = curl_getinfo($ch);
curl_close($ch);

Tap::is( $info['http_code'], 200, 'page request returned a 200 ok response');
Tap::is($res, 'hello 123', 'got back the content I expected');


$ch = curl_init("http://127.0.0.1:11299/shortcircuit.php/idtest/123/");
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
$res = trim( curl_exec($ch) );
$info = curl_getinfo($ch);
curl_close($ch);
Tap::is($res, '<p>id: 123</p>', 'the id in the url was mapped into the request');

$ch = curl_init("http://127.0.0.1:11299/shortcircuit.php/linktest/?");
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
$res = trim( curl_exec($ch) );
$info = curl_getinfo($ch);
Tap::is($res, '<a href="/shortcircuit.php/lt///">linktest</a>', 'Link parameters mapped into a url with correct base url');

$ch = curl_init("http://127.0.0.1:11299/shortcircuit.php/lt/3/2/1/");
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
$res = trim( curl_exec($ch) );
$info = curl_getinfo($ch);

Tap::is($res, '<a href="/shortcircuit.php/lt/3/2/1">linktest</a>', 'params in the url are mapped into a link with correct base url');

$ch = curl_init("http://127.0.0.1:11299/shortcircuit.php");
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
$res = trim(curl_exec($ch));
$info = curl_getinfo($ch);
curl_close($ch);

Tap::is( $info['http_code'], 200, 'tested the entry point with no url args or params');
Tap::like($res, '/site index/i', 'got back the content I expected');

$ch = curl_init("http://127.0.0.1:11299/shortcircuit.php/idtest/john%20wayne/");
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
$res = trim( curl_exec($ch) );
$info = curl_getinfo($ch);
curl_close($ch);
Tap::is($res, '<p>id: john wayne</p>', 'the name with the space in the url was mapped into the request');


use Gaia\ShortCircuit\Resolver;
$r = new Resolver;
$r->setAppDir( __DIR__ . '/app/' );
$expected = array('foo'=>'bar', 'bazz'=>array('1','2','3', 'quux'=>array('a','b','c')));

$link = $r->link('printrequest', $expected );

$ch = curl_init("http://127.0.0.1:11299/shortcircuit.php" . $link);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
$res = json_decode( $raw = trim( curl_exec($ch) ), TRUE);
$info = curl_getinfo($ch);
curl_close($ch);

Tap::cmp_ok( $res, '===', $expected, 'deeply nested complex data structure passed in url');

