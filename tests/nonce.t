#!/usr/bin/env php
<?php
include_once __DIR__ . '/common.php';
use Gaia\Test\Tap;
use Gaia\Nonce;
Tap::plan(4);
$token = 'demo' . time();
$secret = 'abc123demo' . microtime(TRUE);
$n = new Nonce($secret);
Tap::ok( $n->check( $nonce = $n->create( $token, $expires = time() + 1 ), $token ), 'nonce created with 1 left checks out');
Tap::debug($nonce);
Tap::ok( ! $n->check( $nonce = $n->create($token, time() - 1), $token), 'old nonce is expired');
Tap::debug( $nonce );
$n = new Nonce($secret, 10 );
$expires = time() + 10;
$nonce = $n->create( $token, $expires );
Tap::is( strlen( $nonce), 10 * 3 + strlen( $expires), 'nonce chunk length overriden to create a shorter nonce (less secure)');
Tap::debug( $nonce );