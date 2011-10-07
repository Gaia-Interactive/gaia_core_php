#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;
use Gaia\ShortCircuit\Resolver;
use Gaia\ShortCircuit\Controller;
use Gaia\Shortcircuit\Request;
Tap::plan(3);

Router::resolver( new Resolver( __DIR__ . '/app/' ) );

$c = new Controller();
$res = $c->execute('test');
Tap::is( $res, array('test'=>'123'), 'ran an action, got back results');
$res = $c->execute('nested/test');
Tap::is( $res, 1, 'get back 1 on an empty action');
$r = Router::request();
$r->set('abc', '123');
$res = $c->execute('nested/requestmirror');
Tap::is( $res, array('abc'=>'123'), 'requestmirror action returned the var we mapped into the request');