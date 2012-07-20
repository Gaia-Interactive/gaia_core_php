#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
include __DIR__ . '/../common.php';
$skein = new Gaia\Skein\Store( $store = new Gaia\Container() );
include __DIR__ . '/.basic_test_suite.php';
//Tap::debug( $store->all() );