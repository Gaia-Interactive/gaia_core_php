#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Glu;
include __DIR__ . '/../../common.php';
$input = array('a'=>GLU::instance(), 'b'=>new stdclass, 'c'=> new ArrayIterator( array(1,2,3) ) );
include __DIR__ . '/base.php';
Tap::plan(5);
Tap::is( $input, $result_set, 'set works properly' );
Tap::is( $input, $result_get, 'get works properly' );
Tap::is( array_fill_keys( array_keys( $input ), TRUE), $result_isset, 'isset works properly' );
Tap::is( array_fill_keys( array_keys( $input ), NULL), $result_unset, 'unset works properly' );
Tap::is( $glu->non_existent, NULL, 'non-existent variables are null' );
