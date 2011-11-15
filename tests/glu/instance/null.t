#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ .'/../../common.php';
Tap::plan(1);
$arg = NULL;
include __DIR__ . '/base.php';
Tap::is( $result, array(), 'glu data exported is empty array when null is passed in');
