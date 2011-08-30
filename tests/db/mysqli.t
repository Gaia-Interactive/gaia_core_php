#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DB;



DB::load( __DIR__ . '/lib/config.php');
$db = DB::instance('test');
if( $db->connect_error ) Tap::plan('skip_all', $db->connect_error);
Tap::plan(7);
Tap::ok( DB::instance('test') === $db, 'db instance returns same object we instantiated at first');

$rs = $db->execute('SELECT %s as test', 'dummy\'');
Tap::ok( $rs, 'query executed successfully');
Tap::is($rs->fetch_assoc(), array('test'=>'dummy\''), 'sql query preparation works on strings');

$rs = $db->execute('SELECT %i as test', '1112122445543333333333');
Tap::is( $rs->fetch_assoc(), array('test'=>'1112122445543333333333'), 'query execute works injecting big integer in');

$rs = $db->execute('SELECT %i as test', 'dummy');
Tap::is( $rs->fetch_assoc(), array('test'=>'0'), 'query execute sanitizes non integer');

$rs = $db->execute('SELECT %f as test', '1112.122445543333333333');
Tap::is( $rs->fetch_assoc(), array('test'=>'1112.122445543333333333'), 'query execute works injecting big float in');

$rs = $db->execute('SELECT %f as test', 'dummy');
Tap::is( $rs->fetch_assoc(), array('test'=>'0'), 'query execute sanitizes non float');