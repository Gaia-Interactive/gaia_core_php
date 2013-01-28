#!/usr/bin/env php
<?php
use Gaia\Serialize\Compress;

include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(6);

$s = new Compress( new \Gaia\Serialize\PHP(), $threshold = 1);

Tap::is( $s->unserialize( $gzout = $s->serialize( $data = array(1,2,3) )), $data, 'serialize array');
Tap::is( substr($gzout, 0, Compress::LEN), Compress::PREFIX, 'serialized string is prefixed correctly'); 
Tap::is( $s->unserialize( $gzout = $s->serialize( $data = 'testing 1 2 3')), $data, 'serialize string');
Tap::cmp_ok( $s->unserialize( $gzout = $s->serialize( $data = TRUE)), '===', $data, 'serialize boolean');
Tap::is( $s->unserialize( $gzout = $s->serialize( $data = 1245564433 )), $data, 'serialize number');
Tap::is( $s->unserialize( $gzout = $s->serialize( $data = (object) array('foo'=>'bar') )), $data, 'serialize object');
