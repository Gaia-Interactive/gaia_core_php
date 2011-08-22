#!/usr/bin/env php
<?php
$input = array('a'=>1, 'b'=>-2, 'c'=>2.45 );
include __DIR__ . DIRECTORY_SEPARATOR . 'base.php';
use Gaia\Test\Tap;
use Gaia\Container;
Tap::plan(5);
Tap::is( $input, $result_set, 'set works properly' );
Tap::is( $input, $result_get, 'get works properly' );
Tap::is( array_fill_keys( array_keys( $input ), TRUE), $result_isset, 'isset works properly' );
Tap::is( array_fill_keys( array_keys( $input ), NULL), $result_unset, 'unset works properly' );
Tap::is( $c->non_existent, NULL, 'non-existent variables are null' );
