#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\affiliate;
use Gaia\Container;

$affiliate = new Affiliate\Store(new Container);

include __DIR__ . '/.base_test.php';
