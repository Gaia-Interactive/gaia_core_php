#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Request;
Tap::plan(8);

$_REQUEST = array('test'=>'1');
$r = new Request;

Tap::is( $r->test, 1, 'request imports $_REQUEST into a container');

$r = new Request( array('test'=>'2') );

Tap::is( $r->test, 2, 'request imports array into a container');

$r = new Request( array('var1'=> 'hello<script>world</script>') );

Tap::is( $r->var1, 'helloscriptworld/script', 'Variable is filtered by default');
Tap::is( $r->get('var1', 'raw'), 'hello<script>world</script>', 'can still get raw variable');
Tap::is( $r->get('var1', FILTER_SANITIZE_STRIPPED), 'helloworld', 'rip out tags');
Tap::is( $r->get('var1', array(FILTER_SANITIZE_STRING=>FILTER_FLAG_ENCODE_AMP)), 'helloworld', 'rip out tags using option flags');

$_SERVER['REQUEST_URI'] = '/test/a/b/c?hello=1';

$r = new Request();
Tap::is( $r->action(), 'test/a/b/c', 'action extracted from REQUEST_URI' );
$r->setArgs( $args = array('a', 'b', 'c') );
Tap::is( $r->args(), $args, 'args set and retrieved properly');