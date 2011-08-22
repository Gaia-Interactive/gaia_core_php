#!/usr/bin/env php
<?php
$input = array('a'=>'string1', 'coMpLexKey'=>'b', 'fun'=>'run', '__data'=>'test', 'bad-key'=>'test' );
include __DIR__ . DIRECTORY_SEPARATOR . 'base.php';
use Gaia\Test\Tap;
use Gaia\Container;

Tap::plan(5);
Tap::is( $input, $result_set, 'set works properly' );
Tap::is( $input, $result_get, 'get works properly' );
Tap::is( array_fill_keys( array_keys( $input ), TRUE), $result_isset, 'isset works properly' );
Tap::is( array_fill_keys( array_keys( $input ), NULL), $result_unset, 'unset works properly' );
Tap::is( $c->non_existent, NULL, 'non-existent variables are null' );
