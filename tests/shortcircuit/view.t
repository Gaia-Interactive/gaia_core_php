#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;
use Gaia\ShortCircuit\Resolver;
use Gaia\ShortCircuit\View;
Tap::plan(4);

Router::resolver( new Resolver( __DIR__ . '/app/' ) );

$v = new View( array('test'=>'fun') );

$out = $v->fetch('test');
Tap::is($out, 'hello fun', 'fetched the test view correctly with the variable mapped in');

ob_start();
$v->render('test');
$out = ob_get_clean();
Tap::is($out, 'hello fun', 'rendered the test view correctly with the variable mapped in');
$out = $v->fetch('nested/test');
Tap::is($out, realpath(__DIR__ . '/app/nested/test.view.php') . ' fun', 'fetched the nested test view with the variable mapped in');
$out = $v->fetch('nested');
Tap::is($out, realpath(__DIR__ . '/app/nested/index.view.php') . ' fun', 'fetched the nested test index view');
