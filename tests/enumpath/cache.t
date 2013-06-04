#!/usr/bin/env php
<?php

include __DIR__ . '/mysql.setup.php';

$cache = new Gaia\container;
$ttl = 30;

$epath = new Gaia\EnumPath\Cache( $epath, $cache, $ttl );

include __DIR__ .'/.basic_test_suite.php';

//Tap::debug( $cache->all() );