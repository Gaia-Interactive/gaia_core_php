#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ . '/../../../common.php';

Tap::plan(2);

ob_start();
Gaia\GLU::instance(array('exception'=>'exception_string'))->dispatch(__DIR__ . '/../app/error.php');
$output = ob_get_clean();

Tap::like( $output, '/error/i', 'output says error');
Tap::like( $output, '/exception_string/i', 'output says exception_string');
