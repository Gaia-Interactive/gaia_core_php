#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ .'/../../common.php';

$arg = 'test string';
include __DIR__ . '/base.php';
Tap::plan(1);
Tap::is( $result, array(), 'string passed in does nothing');
