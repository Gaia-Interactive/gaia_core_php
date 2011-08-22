#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Container;
$input = array('a'=>new Container(), 'b'=>new stdclass, 'c'=> new ArrayIterator( array(1,2,3) ) );
include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'base.php';
Tap::plan(5);
Tap::is( $input, $result_set, 'set works properly' );
Tap::is( $input, $result_get, 'get works properly' );
Tap::is( array_fill_keys( array_keys( $input ), TRUE), $result_isset, 'isset works properly' );
Tap::is( array_fill_keys( array_keys( $input ), NULL), $result_unset, 'unset works properly' );
Tap::is( $c->non_existent, NULL, 'non-existent variables are null' );
