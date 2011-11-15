<?php
use Gaia\Glu;
include __DIR__ . '/../../common.php';

// Example of a front-end controller

// let's start timing so that later we can display how long it took to run our app.
// this will include the amount of time it took to include the glu framework.
$start = microtime(TRUE );


// determine which controller to call.
$route = GLU::instance($_SERVER)->dispatch( __DIR__ . '/app/util/extract_route.php');

// kick off the app.
// since glu is in the directory (as a symlink), when we start using the glu class here, the main
// glu file is automatically included. later, when we call other classes in our mvc, those classes
// will be automatically included for us as well on the fly.
GLU::instance( array('start'=>$start, 'route'=>$route, 'request'=>$_REQUEST) )->dispatch( __DIR__ . '/app/main.php');

// EOF