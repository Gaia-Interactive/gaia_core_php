#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\IP;

$sample_ip = '102.168.212.226';

Tap::plan(7);

// clear the $_SERVER var;
$_SERVER = array();

Tap::is( $ip = IP::client(), '0.0.0.0', 'baseline from cli is empty ip.');
Tap::cmp_ok( ip2long( $ip ), '===', 0, 'ip2long converts the empty ip to 0');

$_SERVER['REMOTE_ADDR'] = $sample_ip;

Tap::is( $ip = IP::client(), '0.0.0.0', 'even when specifying ip address in remote addr, previous request is cached.');
Tap::is( $ip = IP::client(TRUE), $sample_ip, 'passing in a refresh flag makes the value get updated');
$_SERVER = array();
Tap::is( $ip = IP::client(), $sample_ip, 'call again and the value is cached even with $_SERVER cleared');

$_SERVER = array('HTTP_X_FORWARDED_FOR'=>'192.168.10.144, ' . $sample_ip . ', 102.168.212.225', 'REMOTE_ADDR'=>'102.168.212.227');

Tap::is( $ip = IP::client(TRUE), $sample_ip, 'correct ip address picked up out of the x-forwarded-for header');

$_SERVER = array('HTTP_X_FORWARDED_FOR'=>'127.0.0.1, 192.168.10.144, 10.0.0.1', 'REMOTE_ADDR'=>$sample_ip);

Tap::is( $ip = IP::client(TRUE), '127.0.0.1', 'all the addresses in forwarded-for header were private, using the first one even tho we have something in remote_addr');

