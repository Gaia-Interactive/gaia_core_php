#!/usr/bin/env php
<?php
use Gaia\Test\Tap;

$strict = TRUE;
$path =  __DIR__ . '/lib/non_existent.php';
include __DIR__ . '/base.php';
Tap::plan(5);

Tap::is( $result_dispatch, NULL, 'no return from dispatch');
Tap::is( $result_export_before_dispatch, array(), 'export before dispatch is empty');
Tap::is( $result_export_after_dispatch, NULL, 'never made it to after dispatch');
Tap::isa( $exception, 'Exception', 'exception thrown' );
Tap::like( $exception_message, '/dispatch/i', 'exception message from dispatch');
