#!/usr/bin/env php
<?php
ob_start();
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\Cookie;

Tap::plan(22);
$cookie = new Cookie();


include __DIR__ .'/basic.php';
