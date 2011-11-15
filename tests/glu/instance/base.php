<?php
use Gaia\Glu;

$glu = GLU::instance($arg);
$result = array();
foreach( $glu as $k=>$v) $result[$k] = $v;
