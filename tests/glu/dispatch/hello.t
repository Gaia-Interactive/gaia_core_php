#!/usr/bin/env php
<?php
use Gaia\Test\Tap;

$path = __DIR__ . '/lib/hello.php';
include __DIR__ . '/base.php';
Tap::plan(4);

Tap::is( $result_dispatch, 'hi there', 'return from dispatch gives correct response');
Tap::is( $result_export_before_dispatch, array(), 'export before dispatch is empty');
Tap::is( $result_export_after_dispatch, array(), 'export after dispatch is empty');
Tap::is( $exception, NULL, 'no exception thrown' );
