#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Glu;
include __DIR__ . '/../../../common.php';

Tap::plan(2);

class test_glu extends glu { }

$o = new test_glu(array('greeting'=>'welcome'));
$output = $o->dispatch(__DIR__ . '/../lib/hello.php' );

Tap::like($output, '/welcome/i', 'says welcome');
Tap::like($output, '/from test_glu/i', 'from test_glu class');


// EOF
