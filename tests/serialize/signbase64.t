#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(20);

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

$s = new Gaia\Serialize\SignBase64('test', new Gaia\Serialize\Json(''));


Tap::is( $v = $s->serialize( $data = array(1,2,3) ), 'eXdpPhNG5BxEg6f6ZaDDrQUcOFJFJeKJLJJ_vR4Z2_0.WzEsMiwzXQ', 'serialize array with json core');
Tap::is( $s->unserialize($v), $data, 'unserializes with prefix correctly');

Tap::is( $v = $s->serialize( $data = 'testing' ), 'ecwXTDYgmzszO2ON-4HoSA8aSoj-1L2s1lu0zJPiC0U.InRlc3Rpbmci', 'serialize scalar with json core');
Tap::is( $s->unserialize($v), $data, 'unserializes with scalar value correctly');

Tap::cmp_ok( $v = $s->serialize( $data = TRUE ), '===', '3OIGothZ5i5h6p7BS3kQDoYeUg6VdBIemrFbdRJagOY.dHJ1ZQ', 'serialize boolean with json core');
Tap::cmp_ok( $s->unserialize($v), '===', $data, 'unserializes boolean correctly');

Tap::is( $v = $s->serialize( $data = 1245564433 ), 'jAHdg9UrSFJcgjJpvWUL0uOtjNaZ_BHZ0K30hGBT2YI.MTI0NTU2NDQzMw', 'serialize number value with json core');
Tap::is( $s->unserialize($v), $data, 'unserializes with number value correctly');


Tap::is( $v = $s->serialize( $data = (object) array('foo'=>'bar')), 'aQs55Q0aM3ujejzFFA4b8AwpKc8lKPx2olpwGRw9iuQ.eyJmb28iOiJiYXIifQ', 'serialize object with json core');
Tap::is( $s->unserialize($v), (array) $data, 'unserializes object correctly as assoc array');
