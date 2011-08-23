#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;

Tap::plan(2);

Router::config()->appdir = __DIR__ . '/lib/';
ob_start();
Router::dispatch('demo/index');
$out = ob_get_clean();
Tap::like( $out, '/hello/i', 'hello world renders' );
Tap::debug( $out );

Router::request()->set('title', 'Hello Jack!');
Router::request()->set('message', 'How are you?');
ob_start();
Router::dispatch('demo/echo');
$out = ob_get_clean();
Tap::like( $out, '/hello jack/i', 'dynamic message renders' );
Tap::debug( $out );
