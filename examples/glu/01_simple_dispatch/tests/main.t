#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Glu;
include __DIR__ . '/../../../common.php';

Tap::plan(2);

ob_start();
GLU::instance()->dispatch(__DIR__ . '/../lib/main.php');
$output = ob_get_clean();

Tap::like( $output, '/hello/i', 'says hello');
Tap::like( $output, '/from gaia\\\glu/i', 'from glu class');
