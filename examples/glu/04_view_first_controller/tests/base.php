<?php
include __DIR__ . '/../../../common.php';
include __DIR__ . '/../../../../tests/assert/dom_installed.php';
use Gaia\Glu;
if( ! isset( $input ) ) $input = NULL;
$vars = array('view'=>$view, 'start'=>$start = microtime(TRUE), 'request'=>$input);
ob_start();
GLU::instance( $vars )->dispatch(__DIR__ . '/../app/main.php');
$output = trim(ob_get_clean());
$dom = new DOMDocument();
$dom->loadHTML( $output );


// EOF