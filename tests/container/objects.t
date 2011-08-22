#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Container;
$input = array('a'=>new Container(), 'b'=>new stdclass, 'c'=> new ArrayIterator( array(1,2,3) ) );
include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'base.php';
