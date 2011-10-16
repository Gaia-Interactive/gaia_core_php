#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';

use Gaia\Test\Tap;
use Gaia\ShortCircuit;
use Gaia\Cache;

Tap::plan(2);
$_SERVER['REQUEST_URI'] = '/';
$resolver = new ShortCircuit\Resolver(__DIR__ . '/app/');
ShortCircuit\Router::resolver( $resolver );
ob_start();
$start = microtime(TRUE);
ShortCircuit\Router::run();
$elapsed = number_format(microtime(TRUE) - $start, 6);
$out = ob_get_clean();
Tap::like( $out, '/home page/i', 'index renders' );
Tap::cmp_ok($elapsed, '<', 0.1, "page rendering took less than 0.1 secs: $elapsed");
Tap::debug( $out );
