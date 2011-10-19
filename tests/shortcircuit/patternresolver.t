#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit\PatternResolver;
Tap::plan(7);

$patterns = array(
    'nested/test'=> array(
                    'regex'=>'#^/go/([0-9]+?)$#i', 
                    'params'=>array('id')
                    ),
                    
    'nested/deeply/test' => array(
                    'regex'=>'#^/foo/bar/([a-z]+)/test/([a-z]+)$#i',
                    'params'=>array('a','b')
                    ),
                    
    'index' =>'#^/$#',
);

$r = new PatternResolver( __DIR__ . '/app/', $patterns);
Tap::is( $r->match('/', $args = array()), 'index', 'default url matched index');
Tap::is( $r->match('/go/123', $args), 'nested/test', 'go url matched action' );
Tap::is( $args['id'], '123', 'number extracted into the request id');
Tap::is( $r->match('/foo/bar/bazz/test/quux', $args ), 'nested/deeply/test', 'deeply nested url matched action' );
Tap::is( $args, array(0=>'bazz', 1=>'quux', 'a'=>'bazz', 'b'=>'quux'), 'extracted the correct args');
Tap::is( $r->link('nested/test', array('id'=>123) ), '/go/123', 'pattern converted back into a url' );
Tap::is( $r->link('nested/deeply/test', array('b'=>'quux', 'a'=>'bazz', 'c'=>'test', 0=>'bazz')), '/foo/bar/bazz/test/quux?c=test', 'converted longer pattern with several parts into url');
