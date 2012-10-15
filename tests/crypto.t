#!/usr/bin/env php
<?php
include __DIR__ . '/common.php';
include __DIR__ . '/assert/mcrypt_installed.php';
use Gaia\Test\Tap;
use Gaia\Crypto;

Tap::plan(10);

$input = rtrim(file_get_contents( __DIR__ . '/sample/i_can_eat_glass.txt'), "\0");

$secret = 'my super secret password';

$crypto = new Crypto( $secret );

$encrypted = $crypto->encrypt( $input );

Tap::ok( strlen( $encrypted ) > 0, 'encrypt returns a string');
Tap::ok( $encrypted != $input, 'the encrypted string doesnt match the input');

$output = trim($crypto->decrypt( $encrypted ));

Tap::is( $output, $input, 'decrypted the encrypted string and got back my original input');

