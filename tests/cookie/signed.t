#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\SignedCookie;

Tap::plan(21);
$cookie = new SignedCookie();


include __DIR__ .'/basic.php';
