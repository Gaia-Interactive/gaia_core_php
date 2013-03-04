#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
Tap::plan(5);

$s = new Gaia\Serialize\Int;

Tap::is( $v = $s->serialize( $data = 1), '1', 'serialize integer');
Tap::is( $s->unserialize($v), $data, 'unserializes correctly');


Tap::is( $v = $s->serialize( $data = '111143222323443224322322'), '111143222323443224322322', 'serialize big integer');
Tap::is( $s->unserialize($v), $data, 'unserializes correctly');


$err = '';

try {

    $s->serialize( NULL );
} catch( Exception $e ){
    $err = $e->__toString();
}

Tap::like($err, '/invalid integer/', 'tosses an exception when non integer passed in');