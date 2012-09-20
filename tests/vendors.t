#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
use Gaia\Test\Tap;
Tap::plan(5);
Tap::ok(class_exists('BaseFacebook'), 'basefacebook class exists');
Tap::ok(class_exists('Facebook'), 'facebook class exists');
Tap::ok(class_exists('Predis\Client'), 'predis\client class exists');
Tap::ok(class_exists('Pheanstalk'), 'pheanstalk class exists');
Tap::ok(class_exists('sfYaml'), 'sfYaml class exists');