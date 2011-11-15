<?php
use Gaia\Glu;

include __DIR__ . '/../../common.php';

$export = $glu = $result_dispatch = $result_export_after_dispatch = $result_export_after_dispatch = $exception = $exception_message = NULL;
try {
    $glu = GLU::instance( $arg );
    $export = array();
    foreach( $glu as $k=>$v) $export[$k] = $v;
    $result_export_before_dispatch = $export;
    $result_dispatch = $glu->dispatch(__DIR__ . '/lib/string.php');
    $export = array();
    foreach( $glu as $k=>$v) $export[$k] = $v;
    $result_export_after_dispatch = $export;
} catch( \Exception $e ){
    $exception = $e;
    $exception_message = $e->getMessage();
}

// EOF