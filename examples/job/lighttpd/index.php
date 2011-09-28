<?php
include __DIR__ . '/../../common.php';
$nonce = new Gaia\Nonce('test001');
$server = new Gaia\Container( $_SERVER );
$hash = $server->HTTP_X_JOB_NONCE;
$id = $server->HTTP_X_JOB_ID;
$uri = $server->REQUEST_URI;
print( "\n" . $nonce->create( $uri ) . "\n" );
$valid = $nonce->check($hash, $uri );
$status = ( $valid ) ? 'OK' : 'ERR';
if( $id ) header('X-JOB-ID: ' . $id);
header('X-JOB-STATUS: ' . $status);
if( ! $valid ) header( $server->SERVER_PROTOCOL . ' 404 Not Found');
echo "\n<h1>$status</h1>\n";

// EOF