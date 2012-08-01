#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\Affiliate;
use Gaia\Container;

$affiliate = new Affiliate\Store( new Container() );
$affiliate = new Affiliate\Cache( $affiliate, $cache = new Container() );

include __DIR__ . '/.base_test.php';

//Tap::debug( $cache->all() );