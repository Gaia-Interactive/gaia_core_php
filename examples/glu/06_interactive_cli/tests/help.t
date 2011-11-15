#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ . '/../../../common.php';


Tap::plan(5);

ob_start();
Gaia\GLU::instance()->dispatch(dirname(__DIR__) . '/app/action/help.php');
$output = ob_get_clean();

Tap::like( $output, '/type a command/i', 'output says type a command');
Tap::like( $output, '/help/i', 'output says help');
Tap::like( $output, '/hello/i', 'output says hello');
Tap::like( $output, '/now/i', 'output says now');
Tap::like( $output, '/quit/i', 'output says quit');
