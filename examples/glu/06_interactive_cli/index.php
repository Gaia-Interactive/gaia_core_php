<?php
// an example of a CLI app.

// make sure we are running from cli.
if( ! is_resource( STDIN ) ){
    // exit with an error message for the web browser.
    die('<h1>Please run this from CLI.</h1><h2>Does not work in browser.</h2>');
}

// include glu
include __DIR__ . '/../../common.php';

// kick it off, reading from STDIN
Gaia\GLU::instance(array('STDIN'=>STDIN) )->dispatch(__DIR__ . '/app/main.php');

// EOF
