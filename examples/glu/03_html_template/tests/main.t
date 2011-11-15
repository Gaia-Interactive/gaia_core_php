#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ . '/../../../common.php';
Tap::plan(5);

ob_start();
Gaia\GLU::instance()->dispatch(__DIR__ . '/../lib/main.php');
$output = trim( ob_get_contents() );
ob_end_clean();

Tap::is( substr( $output, 0, 6), '<html>', 'output starts with opening html tag');
Tap::is( substr( $output, -7), '</html>', 'output ends with closing html tag');
Tap::like($output, '#<title>TPL</title>#i', 'output has tpl title');
Tap::like($output, '#<h1>template example</h1>#i', 'output has correct h1 tag');
Tap::like($output, '#<p>shows how to build a templating system</p>#i', 'output has correct content inside p tag');


// EOF