#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit;
use Gaia\ShortCircuit\Resolver;
use Gaia\ShortCircuit\Controller;
use Gaia\Shortcircuit\Request;
use Gaia\Shortcircuit\View;
Tap::plan(9);

class MyRequest extends Request {}
class MyController extends Controller {}
class MyResolver extends Resolver {}
class MyView extends View {}


ShortCircuit::resolver( $resolver = new MyResolver( $appdir = __DIR__ . '/app/' ) );

Tap::is( ShortCircuit::appdir(), $appdir, 'appdir returned from router matches what we passed to resolver');

ShortCircuit::setAppDir( $appdir .= '1/');

Tap::is( ShortCircuit::appdir(), $appdir, 'changed appdir, reflected in the appdir method');
Tap::is( $resolver->appdir(), $appdir, 'shows up in the resolver as well');

ShortCircuit::request( $request = new MyRequest(array('test'=>1)) );
Tap::ok( $request === ShortCircuit::request(), 'request passed in is stored in the router');

ShortCircuit::view( $view = new MyView(array('test'=>1)) );
Tap::ok( $view === ShortCircuit::view(), 'view passed in is stored in the router');

ShortCircuit::controller( $controller = new MyController(array('test'=>1)) );
Tap::ok( $controller === ShortCircuit::controller(), 'controller passed in is stored in the router');

$_SERVER['REQUEST_URI'] = '/test/';
ShortCircuit::request( $request = new MyRequest() );
ShortCircuit::resolver( new MyResolver( $appdir = __DIR__ . '/app/' ) );
ob_start();
ShortCircuit::run();
$out = ob_get_clean();
Tap::is( $out, 'hello 123', 'run called the action and mapped the var into the view');


$_SERVER['REQUEST_URI'] = '/exceptiontest/';
ShortCircuit::request( $request = new Request() );
ShortCircuit::resolver( new Resolver( $appdir = __DIR__ . '/app/' ) );
ob_start();
ShortCircuit::run();
$out = ob_get_clean();
Tap::is( $out, 'testing exception', 'exception caught and passed to action error view');

$_SERVER['REQUEST_URI'] = '/unmanagedexceptiontest/';
ShortCircuit::request( $request = new Request() );
ShortCircuit::resolver( new Resolver( $appdir = __DIR__ . '/app/' ) );
ob_start();
ShortCircuit::run();
$out = ob_get_clean();
Tap::like( $out, '/default error handler/i', 'exception caught and passed to default error view');
