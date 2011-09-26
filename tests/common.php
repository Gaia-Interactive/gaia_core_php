<?php

// create a function for automagically loading all the dependent classes.
require __DIR__ . '/../autoload.php';
require __DIR__ . '/../vendor/autoload.php';

if( strpos(php_sapi_name(), 'apache') !== FALSE ) print "\n<pre>\n";
