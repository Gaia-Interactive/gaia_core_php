#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(28);

$s = new Gaia\Serialize\QueryString();

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), '0=1&1=2&2=3', 'serialize array');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'testing', 'serialize scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', '1', 'serialize boolean');
Tap::cmp_ok( $s->unserialize($v), '===', '1', 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), '1245564433', 'serialize number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), 'foo=bar', 'serialize object');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object correctly as assoc array');

Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar', 'bazz'=>array(1,2,3))), 'foo=bar&bazz[0]=1&bazz[1]=2&bazz[2]=3', 'serialize complex object');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object as nested assoc array');

Tap::is( $v = $s->serialize( $data = array('foo'=>'bar', 'bazz'=>array(1,2,3, 'quux'=>array('a','b','c')))), 'foo=bar&bazz[0]=1&bazz[1]=2&bazz[2]=3&bazz[quux][0]=a&bazz[quux][1]=b&bazz[quux][2]=c', 'serialize complex nested array');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes nested assoc array');

Tap::is( $v = $s->serialize( $data = array('test'=>'Tom=You&Zach=Me') ), 'test=Tom%3DYou%26Zach%3DMe', 'serialize string with url encodable values in it');
Tap::is( $s->unserialize($v), $data, 'unserialize urlencoded string');


Tap::is( $v = $s->serialize( $data = array('test'=>array("Š","š","Đ","đ","Č","č", "Ć","ć","Ž","ž")) ), 'test[0]=%C5%A0&test[1]=%C5%A1&test[2]=%C4%90&test[3]=%C4%91&test[4]=%C4%8C&test[5]=%C4%8D&test[6]=%C4%86&test[7]=%C4%87&test[8]=%C5%BD&test[9]=%C5%BE', 'serialize strings with utf8 values in it');
Tap::is( $s->unserialize($v), $data, 'unserialize utf8 strings');

Tap::is( $v = $s->serialize( $data = 'john wayne' ), 'john%20wayne', 'serialize string with space in it');
Tap::is( $s->unserialize($v), $data, 'unserializes with string with space correctly');

Tap::is( $v = $s->serialize( $data = array('name'=>'john wayne') ), 'name=john%20wayne', 'serialize array containing string with space in it');
Tap::is( $s->unserialize($v), $data, 'unserializes array containing string with space correctly');


Tap::is( $v = $s->serialize( $data = array('test'=>array('name'=>'john wayne') ) ), 'test[name]=john%20wayne', 'serialize nested array containing string with space in it');
Tap::is( $s->unserialize($v), $data, 'unserializes nested array containing string with space correctly');

Tap::is( $v = $s->serialize( $data = array('test'=>array('name'=>array('john wayne') ) ) ), 'test[name][0]=john%20wayne', 'serialize deep nested array containing string with space in it');
Tap::is( $s->unserialize($v), $data, 'unserializes deeply nested array containing string with space correctly');


Tap::todo_start();
Tap::is( $v = $s->serialize( $data = array('go'=>array('test'=>array('name'=>array('john wayne') ) ) ) ), 'go[test][name][0]=john%20wayne', 'serialize deep nested array containing string with space in it');
Tap::is( $s->unserialize($v), $data, 'unserializes deeply nested array containing string with space correctly');
Tap::todo_end();

