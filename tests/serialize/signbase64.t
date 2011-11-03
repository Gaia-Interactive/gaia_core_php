#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(10);

$s = new Gaia\Serialize\SignBase64('test');

Tap::is( $v = $s->serialize( $data = array(1,2,3) ), 'FrobDm5NG0DXUmOjy0gAk0aZikii3KIIU29BJPzBhOk.YTozOntpOjA7aToxO2k6MTtpOjI7aToyO2k6Mzt9', 'serialize array');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'W2f1o0_GK5l3zXuLuAEl_Re1ygvYEWosakbWbXERhVw.czo3OiJ0ZXN0aW5nIjs', 'serialize scalar value');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', 'RXfvQFH9-k4MguJW6mhWY_Q7ZUuprHD89h1UIg-RIos.YjoxOw', 'serialize boolean');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), 'bKoSvosaa5nPHV7m7atUOmqZJ7Pf6640-pS1PVgodlA.aToxMjQ1NTY0NDMzOw', 'serialize number value');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), 'o3VZU_4-JiZrkm-CX8zpTPAodFwtBpOGKTVb5XzGNZc.Tzo4OiJzdGRDbGFzcyI6MTp7czozOiJmb28iO3M6MzoiYmFyIjt9', 'serialize object');
Tap::is( $s->unserialize($v), $data, 'unserializes object correctly');
