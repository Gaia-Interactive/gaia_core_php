#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
$input = array('a'=>'string1', 'coMpLexKey'=>'b', 'fun'=>'run', '__data'=>'test', 'bad-key'=>'test' );
include __DIR__ . '/base.php';
Tap::plan(5);
Tap::is( $input, $result_set, 'set works properly' );
Tap::is( $input, $result_get, 'get works properly' );
Tap::is( array_fill_keys( array_keys( $input ), TRUE), $result_isset, 'isset works properly' );
Tap::is( array_fill_keys( array_keys( $input ), NULL), $result_unset, 'unset works properly' );
Tap::is( $glu->non_existent, NULL, 'non-existent variables are null' );
