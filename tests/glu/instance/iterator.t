#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ .'/../../common.php';

$arg = new ArrayIterator($inner = array('a', 'b', 'c'));
include __DIR__  . '/base.php';
Tap::plan(1);
Tap::is( $result, $inner, 'glu data exported into new from iterator');
