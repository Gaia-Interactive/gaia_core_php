#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Glu;

include __DIR__ . '/../../../common.php';

Tap::plan(4);

ob_start();
Gaia\GLU::instance()->dispatch(__DIR__ . '/../app/action/now.php');
$output = ob_get_clean();

Tap::like( $output, '/The time is/i', 'output says time is ...');
Tap::like( $output, '/GMT/i', 'output says GMT');

ob_start();
GLU::instance(array('now'=>'1213286964', 'timezone'=>'GMT', 'format'=>'Y/m/d H:i:s e'))->dispatch(__DIR__ . '/../app/action/now.php');
$output = ob_get_clean();

Tap::like( $output, '#2008/06/12 16:09:24 GMT#i', 'output prints correct GMT formatted time');


ob_start();
GLU::instance(array('now'=>'1213286963', 'timezone'=>'UTC', 'format'=>'Y/m/d H:i:s e'))->dispatch(__DIR__ . '/../app/action/now.php');
$output = ob_get_clean();

Tap::like( $output, '#2008/06/12 16:09:23 UTC#i', 'output prints correct UTC formatted time');

