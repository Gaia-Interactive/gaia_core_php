#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Resolver;
use  Gaia\ShortCircuit\CachedResolver;
use Gaia\Store\KVP;
Tap::plan(27);
$r = new CachedResolver( new Resolver, new KVP);

Tap::ok($r instanceof \Gaia\ShortCircuit\Iface\Resolver, 'able to instantiate the resolver');
Tap::is($r->appdir(), '', 'by default, nothing in appdir');
$r = new CachedResolver( new Resolver('test'), new KVP );
Tap::is( $r->appDir(), 'test', 'arg passed to constructor sets appdir');
$r->setAppDir('test2');
Tap::is( $r->appdir(), 'test2', 'setAppDir() method changes appdir');

$r->setAppDir( __DIR__ . '/app/' );

Tap::is( $r->get('test', 'action'), __DIR__ . '/app/test.action.php', 'getting path to an action');
Tap::is( $r->get('', 'action'), __DIR__ . '/app/index.action.php', 'getting path to index action');
Tap::is( $r->get('nested', 'action'), __DIR__ . '/app/nested.action.php', 'getting path to nested index action');
Tap::is( $r->get('nested/test', 'action'), __DIR__ . '/app/nested/test.action.php', 'getting path to nested touch action');

Tap::is( $r->get('test', 'view'), __DIR__ . '/app/test.view.php', 'getting path to a view');
Tap::is( $r->get('', 'view'), __DIR__ . '/app/index.view.php', 'getting path to index view');
Tap::is( $r->get('nested', 'view'), __DIR__ . '/app/nested.view.php', 'getting path to nested index view');
Tap::is( $r->get('nested/test', 'view'), __DIR__ . '/app/nested/test.view.php', 'getting path to nested touch view');

Tap::is( $r->match('nested/test/1/r3', $args), 'nested/test', 'match test resolves correctly');
Tap::is( $r->match('nested/xxx/1/r3',  $args), 'nested', 'match index resolves correctly');
Tap::is( $r->match('',  $args), 'index', 'empty match resolves to index');
Tap::is( $r->match('badpath/1/1',  $args), '', 'bad path resolves to nothing');
Tap::is( $r->match('nested/deep/test/1/1/1',  $args), 'nested/deep/test', 'match traverses into a folder without an index');
Tap::is( $r->match('nested/deep/test',  $args), 'nested/deep/test', 'match finds deep match even when it is exact match');
Tap::is( $r->match('nested/deep/no/1/1/1',  $args), 'nested', 'if it doesnt find it drops back down');

$patterns = array(
'/go/(id)' => 'nested/test',
'/foo/bar/(a)/test/(b)' => 'nested/deep/test',
);

$r->setPatterns( $patterns );
Tap::is( $r->match('/', $args), 'index', 'default url matched index');
Tap::is( $r->match('/go/123', $args), 'nested/test', 'go url matched action' );
Tap::is( $args['id'], '123', 'number extracted into the request id');
Tap::is( $r->match('/foo/bar/bazz/test/quux', $args ), 'nested/deep/test', 'deeply nested url matched action' );
Tap::is( $args, array(0=>'bazz', 1=>'quux', 'a'=>'bazz', 'b'=>'quux'), 'extracted the correct args');
Tap::is( $r->link('nested/test', array('id'=>123) ), '/go/123', 'pattern converted back into a url' );
Tap::is( $r->link('nested/deep/test', array('b'=>'quux', 'a'=>'bazz', 'c'=>'test')), '/foo/bar/bazz/test/quux?c=test', 'converted longer pattern with several parts into url');
Tap::is( $r->match('nested/deep/test/1', $args), 'nested/deep/test', 'without a pattern match, falls back on the core match method');