#!/usr/bin/env php
<?php
include_once __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\Nonce;
Tap::plan(4);
$token = 'demo' . time();
$secret = 'abc123demo' . microtime(TRUE);
Nonce::setSecret($secret);
Tap::ok( Nonce::check( $nonce = Nonce::create( $token, $expires = time() + 1 ), $token ), 'nonce created with 1 left checks out');
Tap::is( strlen( $nonce ), 40 + strlen( $expires ), 'nonce digest is a sha1 with the expires string at the end');
Tap::debug($nonce);
Tap::ok( ! Nonce::check( $nonce = Nonce::create($token, time() - 1), $token), 'old nonce is expired');
Tap::debug( $nonce );
Nonce::setDigestLength( 10 );
$expires = time() + 10;
$nonce = Nonce::create( $token, $expires );
Tap::is( strlen( $nonce), 10 + strlen( $expires), 'nonce digest overriden to create a shorter nonce (less secure)');
Tap::debug( $nonce );