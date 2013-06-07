#!/usr/bin/env php
<?php

include __DIR__ . '/mysql.setup.php';

$create_identifier = function( $table = NULL ) use ( $create_identifier ){
    return new Gaia\Identifier\Wrap( $create_identifier($table));
};

include __DIR__ .'/basic_test_suite.php';
