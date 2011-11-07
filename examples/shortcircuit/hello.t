#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit;

Tap::plan(4);
$_SERVER['REQUEST_URI'] = '/hello/';
ShortCircuit::setAppDir( __DIR__ . '/app/');
ob_start();
ShortCircuit::run();
$out = ob_get_clean();
Tap::like( $out, '/hello/i', 'hello world renders' );
Tap::debug( $out );

ShortCircuit::request()->set('title', 'Hello Jack!');
ShortCircuit::request()->set('message', 'How are you?');
ob_start();
ShortCircuit::dispatch('hello/echo');
$out = ob_get_clean();
Tap::like( $out, '/hello jack/i', 'dynamic message renders' );
Tap::debug( $out );

ShortCircuit::request()->set('title', 'Hello Jack!');
ShortCircuit::request()->set('message', 'How are you?');
ob_start();
ShortCircuit::dispatch('/hellosymlink');
$out = ob_get_clean();
Tap::like( $out, '/hello jack/i', 'symlink message renders' );
Tap::debug( $out );

ob_start();
ShortCircuit::dispatch('hello/greetings/');
$out = ob_get_clean();
Tap::like( $out, '/(howzit|wazzup|yo yo yo)/i', 'random greeting renders' );
Tap::debug( $out );
