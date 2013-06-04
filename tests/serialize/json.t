#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(20);

$s = new Gaia\Serialize\Json;

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), '#__JSON__:[1,2,3]', 'serialize array with prefix');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'testing', 'serialize without prefix for scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', '#__JSON__:true', 'serialize boolean with prefix');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), '1245564433', 'serialize without prefix for number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), '#__JSON__:{"foo":"bar"}', 'serialize object');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object as assoc array');


$s =new Gaia\Serialize\Json($prefix = '');

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), '[1,2,3]', 'serialize array without prefix');
Tap::is( $s->unserialize($v), $data, 'unserializes without prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), '"testing"', 'serialize without prefix for scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', 'true', 'serialize boolean without prefix');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), '1245564433', 'serialize number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');

Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), '{"foo":"bar"}', 'serialize object with no prefix');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object as assoc array');
