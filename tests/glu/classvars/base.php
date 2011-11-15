<?php
use Gaia\Glu;

include __DIR__ . '/../../common.php';

$glu = GLU::instance();
foreach( array('result_set', 'result_get', 'result_isset', 'result_unset') as $key ){
    $$key = array();
}
if( ! isset( $input ) || ! is_array( $input ) ) $input = array();
foreach( $input as $k=>$v ){
    $result_set[ $k ] = $glu->$k = $v;
    $result_isset[ $k ] = isset( $glu->$k );
    $result_get[ $k ] = $glu->$k;
    unset( $glu->$k );
    $result_unset[ $k ] = $glu->$k;
}

