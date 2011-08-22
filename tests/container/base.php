<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Container;

$c = new Container();
foreach( array('result_set', 'result_get', 'result_isset', 'result_unset') as $key ){
    $$key = array();
}
if( ! isset( $input ) || ! is_array( $input ) ) $input = array();
foreach( $input as $k=>$v ){
    $result_set[ $k ] = $c->$k = $v;
    $result_isset[ $k ] = isset( $c->$k );
    $result_get[ $k ] = $c->$k;
    unset( $c->$k );
    $result_unset[ $k ] = $c->$k;
}

Tap::is( $input, $result_set, 'set works properly' );
Tap::is( $input, $result_get, 'get works properly' );
Tap::is( array_fill_keys( array_keys( $input ), TRUE), $result_isset, 'isset works properly' );
Tap::is( array_fill_keys( array_keys( $input ), NULL), $result_unset, 'unset works properly' );
Tap::is( $c->non_existent, NULL, 'non-existent variables are null' );
