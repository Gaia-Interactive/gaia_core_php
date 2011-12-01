#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Http\Request;
use Gaia\Http\Pool;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/webservice_started.php';

Tap::plan(5);

$pool = new Pool;

$ct = 0;
$iterations = 50;
$requests = array();


$counter = function () use ( & $ct ){
    $ct++;
};

for( $i = 0; $i < $iterations; $i++){
    $r = new Request('http://127.0.0.1:11299/');
    $r->handle = $counter;
    $pool->add( $requests[] =  $r);
}

$start = microtime(TRUE);
$pool->finish();
$elapsed = number_format(microtime(TRUE) - $start,5);

Tap::is( $ct, $iterations, "got $iterations responses back");
Tap::cmp_ok( $elapsed, '<', 1, "took less than 1 sec (actual time is $elapsed s)");

$status = TRUE;
foreach( $requests as $request ){
    if( $request->response->http_code != 200 ) $status = FALSE;
    break;
}

Tap::ok( $status , 'all the responses came back with http code 200');
if( ! $status ) Tap::debug( print_r($request, TRUE) );

$status = TRUE;
foreach( $requests as $request ){
    if( ! preg_match('/index page/i', $request->response->body ) ) $status = FALSE;
    break;
}

Tap::ok( $status , 'all the responses came back with correct body response');
if( ! $status ) Tap::debug( $request->response->body );

$status = TRUE;
$max = 0;
foreach( $requests as $request ){
    if(  $request->response->total_time == 0 || $request->response->total_time > 0.5 ) $status = FALSE;
    if( $request->response->total_time > $max ) $max = $request->response->total_time;
}

Tap::ok( $status , "all the responses came back in less than .5 secs each (max $max s)");
if( ! $status ) Tap::debug( $request->response->total_time );
