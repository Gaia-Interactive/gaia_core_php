<?php
use Gaia\Glu;

include __DIR__ . '/../../common.php';

if( ! isset( $strict ) ) $strict = NULL;
$export = $glu = $result_dispatch = $result_export_after_dispatch = $result_export_after_dispatch = $exception = $exception_message = NULL;
try {
    $glu = GLU::instance();
    $export = array();
    foreach( $glu as $k=>$v) $export[$k] = $v;
    $result_export_before_dispatch = $export;
    $result_dispatch = $glu->dispatch($path, $strict);
    $export = array();
    foreach( $glu as $k=>$v) $export[$k] = $v;
    $result_export_after_dispatch = $export;
} catch( \Exception $e ){
    $exception = $e;
    $exception_message = $e->getMessage();
}
