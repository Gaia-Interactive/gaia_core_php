<?php
// This demo shows how you can nest glus. 
// in other words, one glu can instantiate an dispatch another.
// which allows you to create a complex nested and encapsulated
// components.

// include the autoload.
include __DIR__ . '/../../common.php';

// run main.
Gaia\GLU::instance()->dispatch( __DIR__ . '/lib/main.php');

// all done. 

