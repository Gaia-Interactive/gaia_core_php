<?php
require __DIR__ . '/../../common.php';
include __DIR__ . '/functions.php';
Gaia\DB\Connection::load(array('stockpile_1'=>'mysqli://127.0.0.1/test'));

if( strpos(php_sapi_name(), 'apache') !== FALSE ) print "\n<pre>\n";

$app = 'test1';
