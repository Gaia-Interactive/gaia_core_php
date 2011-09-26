<?php

ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


// create a function for automagically loading all the dependent classes.
require __DIR__ . '/../autoload.php';
require __DIR__ . '/../vendor/autoload.php';
