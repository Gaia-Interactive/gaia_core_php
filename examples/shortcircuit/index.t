#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';

use Gaia\Test\Tap;
use Gaia\ShortCircuit\Router;

Tap::plan(1);
$_SERVER['REQUEST_URI'] = '/';
Router::config()->appdir = __DIR__ . '/app/';
ob_start();
Router::run();
$out = ob_get_clean();
Tap::like( $out, '/home page/i', 'index renders' );
Tap::debug( $out );
