<?php
use Gaia\Glu;

// Example of a view-first controller

// let's start timing so that later we can display how long it took to run our app.
// this will include the amount of time it took to include the glu framework.
$start = microtime(TRUE );

// include an auto-load function for classes, so that we can
// put all of our related classes into this directory and they
// will be automagically included for us.
include __DIR__ . '/../../common.php';

// We are making a view-first controller, so let's determine our view, shall we? I am calling
// a short snippet that tells me what the name of my view is based on the current url.
$view = GLU::instance( $_SERVER )->dispatch(__DIR__ . '/app/lib/extract_view.php');

// set up some arguments
$args = array('view'=>$view, 'start'=>$start, 'request'=>$_REQUEST);

// kick off the app.
// since glu is in the directory (as a symlink), when we start using the glu class here, the main
// glu file is automatically included. later, when we call other classes in our mvc, those classes
// will be automatically included for us as well on the fly.
GLU::instance($args)->dispatch( __DIR__ .'/app/main.php');

// EOF