#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\ShortCircuit;

Tap::plan(1);
$_SERVER['REQUEST_URI'] = '/non/existent/page/';
ShortCircuit::setAppDir( __DIR__ . '/app/' );
ob_start();
ShortCircuit::run();
$out = ob_get_clean();
Tap::like( $out, '/page not found/i', '404 renders' );
Tap::debug( $out );
