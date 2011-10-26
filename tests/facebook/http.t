#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Http\Request;
use Gaia\Store\KVP;

Tap::plan('skip_all', 'work in progress');


if( ! function_exists('curl_init') ){
    Tap::plan('skip_all', 'php curl library not installed');
}

if( ! @fsockopen('127.0.0.1', 11299) ){
    Tap::plan('skip_all', 'http://127.0.0.1:11299/ not started. run ./tests/webservice/start.sh');
}

$config = @include __DIR__ . '/../webservice/.fb.config.php';

if( ! is_array($config) ){
    Tap::plan('skip_all', 'no facebook config info');
}

$parts = parse_url($config['canvaspage']);
$host = $parts['host'];

if( ! $host ){
    Tap::plan('skip_all', 'invalid facebook config: no canvaspage');
}


$fb = new Facebook( $config, $persistence = new KVP );
$res = $fb->api('/' . $fb->getAppId() . '/accounts/test-users');

if( ! is_array( $res ) || ! isset( $res['data'] ) || ! ($testinfo = array_pop( $res['data'] ) )){
    Tap::fail("no test user");
    exit;
}

//print_r( $testinfo );
$useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1';
$cookiefile = '/tmp/fbcookies.txt';

$fbrequest = new Request( $testinfo['login_url'] );
$response = $fbrequest->exec(array(
                CURLOPT_HTTPHEADER => array('User-Agent: ' . $useragent),
                CURLOPT_COOKIEJAR => $cookiefile,
                CURLOPT_FOLLOWLOCATION => 3,

                ));
var_dump( $response );



$canvasrequest = new Request('http://127.0.0.1:11299/fb.php');
$response = $canvasrequest->exec( array(
                CURLOPT_HTTPHEADER => array('Host: '. $host),
                CURLOPT_COOKIEJAR =>  $cookiefile,
                ));
                
$pattern = "#top\.location\.href[\s]?=[\s]?'(*.+)'#i";
$pattern = "#top\.location\.href[\s]?=[\s]?['\"](.+)['\"]#i";


if( ! preg_match($pattern, $response->body, $matches ) ){
    Tap::fail("no redirect link");
    exit;
}
$redirect = $matches[1];
$fbrequest->url = $redirect;
$response = $fbrequest->exec(array(
                CURLOPT_HTTPHEADER => array('User-Agent: ' . $useragent),
                CURLOPT_COOKIEJAR => $cookiefile,
                CURLOPT_FOLLOWLOCATION => 1,
                ));
var_dump( $response );
print "\n";