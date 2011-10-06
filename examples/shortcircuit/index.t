#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';

use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;

Tap::plan(2);
$_SERVER['REQUEST_URI'] = '/';
Router::setAppDir(__DIR__ . '/app/');
ob_start();
$start = microtime(TRUE);
Router::run();
$elapsed = number_format(microtime(TRUE) - $start, 6);
$out = ob_get_clean();
Tap::like( $out, '/home page/i', 'index renders' );
Tap::cmp_ok($elapsed, '<', 0.01, "page rendering took less than 0.1 secs: $elapsed");
Tap::debug( $out );
