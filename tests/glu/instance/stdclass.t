#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ .'/../../common.php';

$arg = new stdclass;
$arg->a = 'test';
$arg->b = 'test';
$arg->c = 'test';
include __DIR__ . '/base.php';
Tap::plan(1);
Tap::is( $result, array(), 'glu data exported is empty array when stdclass objects is passed in');
