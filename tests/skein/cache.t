#!/usr/bin/env php
<?php
use Gaia\Skein;
use Gaia\Container;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';

$skein = new Skein\Cache( new Skein\Store( $store = new Container() ), $cache = new Container() );

include __DIR__ . '/.basic_test_suite.php';


//Tap::debug( $cache->all() );