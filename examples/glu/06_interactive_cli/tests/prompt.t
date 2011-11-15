#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ . '/../../../common.php';

Tap::plan(1);

ob_start();
Gaia\GLU::instance()->dispatch(__DIR__ . '/../app/prompt.php');
$output = ob_get_clean();

Tap::like( $output, '/>/', 'output says >');
