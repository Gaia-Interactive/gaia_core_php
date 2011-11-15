#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ .'/../../common.php';
$arg = array('0'=>'a', 'test'=>'test', '0a'=>'fun', '000'=>'fun', 'valid1'=>'1', 'a'=>'string', 'under_score'=>'test', 'dash-it'=>'test');
include __DIR__ .'/base.php';
Tap::plan(1);
Tap::is( $result, $arg, 'glu data exported into new');
