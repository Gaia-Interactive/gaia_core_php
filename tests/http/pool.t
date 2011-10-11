#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Http\Request;
use Gaia\Http\Pool;

if( ! function_exists('curl_init') ){
    Tap::plan('skip_all', 'php curl library not installed');
}

if( ! @fsockopen('127.0.0.1', '11299')) {
    die("unable to connect to test job url: please run examples/job/lighttpd/start.sh\n");
}

Tap::plan(5);

$pool = new Pool;

$ct = 0;

$requests = array();

$pool->attach( 
    function ( Request $request ) use ( & $ct ){
        $ct++;
    }
);
for( $i = 0; $i < 10; $i++){
    $pool->add( $requests[] = new Request('http://127.0.0.1:11299/') );
}

$start = microtime(TRUE);
$pool->finish();
$elapsed = number_format(microtime(TRUE) - $start,5);

Tap::is( $ct, 10, 'got 10 responses back');
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
    if( ! preg_match('/ok/i', $request->response->body ) ) $status = FALSE;
    break;
}

Tap::ok( $status , 'all the responses came back with body of ok');
if( ! $status ) Tap::debug( $request->response->body );

$status = TRUE;
foreach( $requests as $request ){
    if(  $request->response->total_time == 0 || $request->response->total_time > 0.5 ) $status = FALSE;
    break;
}

Tap::ok( $status , 'all the responses came back in less than .5 secs each');
if( ! $status ) Tap::debug( $request->response->total_time );
