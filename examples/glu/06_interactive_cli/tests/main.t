#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ . '/../../../common.php';

Tap::plan(6);


$file_pointer = fopen(__DIR__ . '/stdin.mock.txt', 'r');
ob_start();
Gaia\GLU::instance(array('STDIN'=>$file_pointer))->dispatch(__DIR__ . '/../app/main' );
$output = ob_get_clean();
fclose( $file_pointer );

Tap::like( $output, '/hello/i', 'output says hello');
Tap::like( $output, '/help/i', 'output says help');
Tap::like( $output, '/good bye/i', 'output says goodbye');
Tap::like( $output, '/error/i', 'output says error');
Tap::like( $output, '/exception/i', 'output says exception');
Tap::ok( strpos( $output, '>') !== FALSE, 'output displays prompt');
