#!/usr/bin/env php
<?php
// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-facebook
require __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\FaceBook\ApiCache as FacebookAPI;
use Gaia\Store\KVP;
use Gaia\Facebook\Persistence as Facebook;
if( ! function_exists('curl_init') ){
    Tap::plan('skip_all', 'php curl library not installed');
}

if( ! @fsockopen('api.facebook.com', '443') ){
    Tap::plan('skip_all', 'unable to connect to facebook api');
}

if( ! class_exists('BaseFacebook') ){
    Tap::plan('skip_all', 'basefacebook class not loaded.');
}

$config = @include __DIR__ . '/config.php';
try {
    
    $fb = new FacebookAPI( new Gaia\Facebook\NoAuth(), $cache = new KVP() );
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(is_array( $config ) ? 7 : 4);

$res = $fb->api('/19292868552');
Tap::ok( is_array( $res ), 'response is an array');
Tap::is( $res['id'], '19292868552', 'returned the facebook id in the json response');
Tap::is( $res['name'], 'Facebook Platform', 'name is facebook platform');
Tap::is( $cache->{'graph/1/985eb366cf312423dec896705ba7e75f13f38812'}['response'], $res, 'cached value matches response');
if( ! is_array( $config ) ) exit;

$fb = new Facebook( $config, $persistence = new KVP );
$res = $fb->api('/19292868552');

Tap::ok( is_array( $res ), 'response is an array');
Tap::is( $res['id'], '19292868552', 'returned the facebook id in the json response');
Tap::is( $res['name'], 'Facebook Platform', 'name is facebook platform');
