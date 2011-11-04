#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(20);

$s = new Gaia\Serialize\PHP;

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), '#__PHP__:a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}', 'serialize array with prefix');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'testing', 'serialize without prefix for scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', '#__PHP__:b:1;', 'serialize boolean with prefix');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), '1245564433', 'serialize without prefix for number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), '#__PHP__:O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}', 'serialize object');
Tap::is( $s->unserialize($v), $data, 'unserializes object correctly');


$s =new Gaia\Serialize\PHP($prefix = '');

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), 'a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}', 'serialize array without prefix');
Tap::is( $s->unserialize($v), $data, 'unserializes without prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 's:7:"testing";', 'serialize without prefix for scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', 'b:1;', 'serialize boolean without prefix');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), 'i:1245564433;', 'serialize number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');

Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}', 'serialize object with no prefix');
Tap::is( $s->unserialize($v), $data, 'unserializes object correctly');
