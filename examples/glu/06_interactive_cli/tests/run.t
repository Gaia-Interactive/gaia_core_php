#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Glu;

include __DIR__ . '/../../../common.php';

Tap::plan(14);

ob_start();
$response = GLU::instance()->dispatch(__DIR__ . '/../app/run.php');
$output = ob_get_clean();

Tap::is( trim($output), '>', 'output is a prompt');
Tap::is( $response, TRUE, 'response is true');

ob_start();
$response = GLU::instance(array('line'=>'hello'))->dispatch(__DIR__ . '/../app/run.php');
$output = ob_get_clean();

Tap::like( $output, '/\>/', 'output has a prompt');
Tap::like( $output, '/hello/i', 'output says hello');
Tap::is( $response, TRUE, 'response is true');


ob_start();
$response = GLU::instance(array('line'=>'help'))->dispatch(__DIR__ . '/../app/run.php');
$output = ob_get_clean();


Tap::like( $output, '/\>/', 'output has a prompt');
Tap::like( $output, '/help/i', 'output says help');
Tap::like( $output, '/type a command/i', 'output gives instructions');
Tap::is( $response, TRUE, 'response is true');

ob_start();
$response = GLU::instance(array('line'=>'now'))->dispatch(__DIR__ . '/../app/run.php');
$output = ob_get_clean();

Tap::like( $output, '/\>/', 'output has a prompt');
Tap::like( $output, '/the time is/i', 'output says the time is');
Tap::is( $response, TRUE, 'response is true');

ob_start();
$response = GLU::instance(array('line'=>'quit'))->dispatch(__DIR__ . '/../app/run.php');
$output = ob_get_clean();

Tap::like( $output, '/good bye/i', 'output says good bye');
Tap::is( $response, FALSE, 'response is false');
