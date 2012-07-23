#!/usr/bin/env php
<?php
use Gaia\Stratum;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';

$stratum = new Stratum\Internal();
include __DIR__ .'/.basic_test_suite.php';
