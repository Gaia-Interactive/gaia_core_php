#!/usr/bin/env php
<?php

include __DIR__ . '/mysql.setup.php';

$create_identifier = function( $table = NULL ) use ( $create_identifier ){
    return new Gaia\Identifier\Cache( $create_identifier($table), new Gaia\Store\KVPTTL, $ttl = 300);
};

include __DIR__ .'/basic_test_suite.php';
