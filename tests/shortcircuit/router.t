#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;
use Gaia\ShortCircuit\Resolver;
use Gaia\ShortCircuit\Controller;
use Gaia\Shortcircuit\Request;
use Gaia\Shortcircuit\View;
Tap::plan(7);

class MyRequest extends Request {}
class MyController extends Controller {}
class MyResolver extends Resolver {}
class MyView extends View {}


Router::resolver( $resolver = new MyResolver( $appdir = __DIR__ . '/app/' ) );

Tap::is( Router::appdir(), $appdir, 'appdir returned from router matches what we passed to resolver');

Router::setAppDir( $appdir .= '1/');

Tap::is( Router::appdir(), $appdir, 'changed appdir, reflected in the appdir method');
Tap::is( $resolver->appdir(), $appdir, 'shows up in the resolver as well');

Router::request( $request = new MyRequest(array('test'=>1)) );
Tap::ok( $request === Router::request(), 'request passed in is stored in the router');

Router::view( $view = new MyView(array('test'=>1)) );
Tap::ok( $view === Router::view(), 'view passed in is stored in the router');

Router::controller( $controller = new MyController(array('test'=>1)) );
Tap::ok( $controller === Router::controller(), 'controller passed in is stored in the router');

$_SERVER['REQUEST_URI'] = '/test/';
Router::request( $request = new MyRequest() );
Router::resolver( new MyResolver( $appdir = __DIR__ . '/app/' ) );
ob_start();
Router::run();
$out = ob_get_clean();
Tap::is( $out, 'hello 123', 'run called the action and mapped the var into the view');