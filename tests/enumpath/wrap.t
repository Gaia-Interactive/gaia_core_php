#!/usr/bin/env php
<?php
use Gaia\EnumPath;

include __DIR__ . '/mysql.setup.php';

$epath = new EnumPath\Wrap( $epath );

include __DIR__ .'/.basic_test_suite.php';

