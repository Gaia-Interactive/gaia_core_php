#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\Resolver;
Tap::plan(19);
$r = new Resolver;

Tap::ok($r instanceof \Gaia\ShortCircuit\Iface\Resolver, 'able to instantiate the resolver');
Tap::is($r->appdir(), '', 'by default, nothing in appdir');
$r = new Resolver('test');
Tap::is( $r->appDir(), 'test', 'arg passed to constructor sets appdir');
$r->setAppDir('test2');
Tap::is( $r->appdir(), 'test2', 'setAppDir() method changes appdir');

$r->setAppDir( __DIR__ . '/app/' );

Tap::is( $r->get('test', 'action'), __DIR__ . '/app/test.action.php', 'getting path to an action');
Tap::is( $r->get('', 'action'), __DIR__ . '/app/index.action.php', 'getting path to index action');
Tap::is( $r->get('nested', 'action'), __DIR__ . '/app/nested/index.action.php', 'getting path to nested index action');
Tap::is( $r->get('nested/test', 'action'), __DIR__ . '/app/nested/test.action.php', 'getting path to nested touch action');

Tap::is( $r->get('test', 'view'), __DIR__ . '/app/test.view.php', 'getting path to a view');
Tap::is( $r->get('', 'view'), __DIR__ . '/app/index.view.php', 'getting path to index view');
Tap::is( $r->get('nested', 'view'), __DIR__ . '/app/nested/index.view.php', 'getting path to nested index view');
Tap::is( $r->get('nested/test', 'view'), __DIR__ . '/app/nested/test.view.php', 'getting path to nested touch view');

Tap::is( $r->match('nested/test/1/r3', $args ), 'nested/test', 'match test resolves correctly');
Tap::is( $r->match('nested/xxx/1/r3',  $args ), 'nested', 'match index resolves correctly');
Tap::is( $r->match('',  $args), 'index', 'empty match resolves to index');
Tap::is( $r->match('badpath/1/1',  $args ), '', 'bad path resolves to nothing');
Tap::is( $r->match('nested/deep/test/1/1/1',  $args ), 'nested/deep/test', 'match traverses into a folder without an index');
Tap::is( $r->match('nested/deep/test',  $args ), 'nested/deep/test', 'match finds deep match even when it is exact match');
Tap::is( $r->match('nested/deep/no/1/1/1',  $args ), 'nested', 'if it doesnt find it drops back down');