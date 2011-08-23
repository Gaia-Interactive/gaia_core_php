#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;

Tap::plan(2);
$_SERVER['REQUEST_URI'] = '/hello/';
Router::config()->appdir = __DIR__ . '/lib/';
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
