#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(18);

$s = new Gaia\Serialize\Yaml();

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), "#__YAML__:- 1\n- 2\n- 3\n", 'serialize array');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'testing', 'serialize scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', '#__YAML__:true', 'serialize boolean');
Tap::cmp_ok( $s->unserialize($v), '===', TRUE, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), '1245564433', 'serialize number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), "#__YAML__:foo: bar\n", 'serialize object');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object correctly as assoc array');

Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar', 'bazz'=>array(1,2,3))), "#__YAML__:foo: bar\nbazz:\n  - 1\n  - 2\n  - 3\n", 'serialize complex object');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object as nested assoc array');

Tap::is( $v = $s->serialize( $data = array('foo'=>'bar', 'bazz'=>array(1,2,3, 'quux'=>array('a','b','c')))), "#__YAML__:foo: bar\nbazz:\n  0: 1\n  1: 2\n  2: 3\n  quux: [a, b, c]\n", 'serialize complex nested array');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes nested assoc array');

Tap::is( $v = $s->serialize( $data = array('test'=>"foo: bar\n") ), '#__YAML__:test: "foo: bar\n"' . "\n", 'serialize string with encodable values in it');
Tap::is( $s->unserialize($v), $data, 'unserialize encoded string');


Tap::is( $v = $s->serialize( $data = array('test'=>array("Š","š","Đ","đ","Č","č", "Ć","ć","Ž","ž")) ), "#__YAML__:test:\n  - 'Š'\n  - š\n  - Đ\n  - đ\n  - Č\n  - č\n  - Ć\n  - ć\n  - Ž\n  - ž\n", 'serialize strings with utf8 values in it');
Tap::is( $s->unserialize($v), $data, 'unserialize utf8 strings');
