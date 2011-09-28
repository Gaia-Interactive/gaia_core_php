#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;

Tap::plan(5);
$_SERVER['REQUEST_URI'] = '/hello/';
Router::config()->appdir = __DIR__ . '/app/';
ob_start();
Router::run();
$out = ob_get_clean();
Tap::like( $out, '/hello/i', 'hello world renders' );
Tap::debug( $out );

Router::request()->set('title', 'Hello Jack!');
Router::request()->set('message', 'How are you?');
ob_start();
Router::dispatch('hello/echo');
$out = ob_get_clean();
Tap::like( $out, '/hello jack/i', 'dynamic message renders' );
Tap::debug( $out );

Router::request()->set('title', 'Hello Jack!');
Router::request()->set('message', 'How are you?');
ob_start();
Router::dispatch('hellosymlink/echo');
$out = ob_get_clean();
Tap::like( $out, '/hello jack/i', 'dynamic message renders' );
Tap::debug( $out );

ob_start();
Router::dispatch('hello/greetings/extra/args');
$out = ob_get_clean();
Tap::like( $out, '/(howzit|wazzup|yo yo yo)/i', 'random greeting renders' );
Tap::debug( $out );
Tap::is( $args = Router::request()->getArgs(), array('extra', 'args'), 'extra args set correctly' );