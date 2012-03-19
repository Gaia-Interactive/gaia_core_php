#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(20);

$s = new Gaia\Serialize\Base64();

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), 'YTozOntpOjA7aToxO2k6MTtpOjI7aToyO2k6Mzt9', 'serialize array');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'czo3OiJ0ZXN0aW5nIjs', 'serialize scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', 'YjoxOw', 'serialize boolean');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), 'MTI0NTU2NDQzMw', 'serialize number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), 'Tzo4OiJzdGRDbGFzcyI6MTp7czozOiJmb28iO3M6MzoiYmFyIjt9', 'serialize object');
Tap::is( $s->unserialize($v), $data, 'unserializes object correctly');

$s = new Gaia\Serialize\Base64(new Gaia\Serialize\Json(''));


Tap::is( $v = $s->serialize( $data = array(1,2,3) ), 'WzEsMiwzXQ', 'serialize array with json core');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'InRlc3Rpbmci', 'serialize scalar with json core');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', 'dHJ1ZQ', 'serialize boolean with json core');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), 'MTI0NTU2NDQzMw', 'serialize number value with json core');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), 'eyJmb28iOiJiYXIifQ', 'serialize object with json core');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object correctly as assoc array');
