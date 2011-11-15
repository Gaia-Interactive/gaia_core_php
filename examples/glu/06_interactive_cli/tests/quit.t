#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ . '/../../../common.php';

Tap::plan(2);

ob_start();
$response = \Gaia\GLU::instance()->dispatch(__DIR__ . '/../app/action/quit.php');
$output = ob_get_clean();

Tap::like( $output, '/good bye/i', 'output says good bye');
Tap::ok( ! $response, 'response from dispatch is FALSE');
