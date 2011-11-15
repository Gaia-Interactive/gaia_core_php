#!/usr/bin/env php
<?php
use Gaia\GLU;
use Gaia\Test\Tap;
include __DIR__ .'/../../common.php';

$arg =  GLU::instance();
$arg->test = 'test string';
include __DIR__ . '/base.php';
Tap::plan(2);

$export = array();
foreach( $arg as $k=>$v) $export[$k] = $v;
Tap::is( $result, $export, 'export returns arg');
Tap::is( $result['test'], $arg->test, 'glu data exported into new');

// EOF