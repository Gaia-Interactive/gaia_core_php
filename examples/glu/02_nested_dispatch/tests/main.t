#!/usr/bin/env php
<?php
// include the glu
use Gaia\Test\Tap;
include __DIR__ . '/../../../common.php';

Tap::plan(2);

ob_start();
Gaia\GLU::instance()->dispatch(__DIR__ . '/../lib/main.php');
$output = ob_get_contents();
ob_end_clean();

Tap::like($output, '/hello/i', 'says hello');
$path = str_replace('/', '\\' . DIRECTORY_SEPARATOR, 'level1/level2/level3/level5/hello.php');
Tap::like( $output, '/' . $path . '/i', 'reached correct nesting');  


// EOF