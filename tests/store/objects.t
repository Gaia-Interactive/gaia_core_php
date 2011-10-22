#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
$input = array('a'=>new Gaia\Store\Container(), 'b'=>new stdclass, 'c'=> new ArrayIterator( array(1,2,3) ) );
include dirname(__FILE__) . DIRECTORY_SEPARATOR . 'base.php';
