#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
$arg = '';
include __DIR__ . '/base.php';
Tap::plan(4);

Tap::is( $result_export_before_dispatch, array(), 'export is empty before dispatch');
Tap::is( $result_export_after_dispatch, array(), 'export is empty after dispatch');
Tap::is( $result_dispatch, 'hello', 'dispatch runs string, returns hello' );
Tap::is( $exception, NULL, 'no exception thrown' );
