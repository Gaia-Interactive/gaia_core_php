#!/usr/bin/env php
<?php
// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-facebook
require __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\FaceBook\ApiCache as FacebookAPI;
use Gaia\Container;
use Gaia\Facebook\Persistence as Facebook;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/basefacebook_installed.php';
include __DIR__ . '/../assert/facebook_api_connect.php';

$config = @include __DIR__ . '/.config.php';
try {
    
    $fb = new FacebookAPI( new Gaia\Facebook\NoAuth(), $cache = new Container() );
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(is_array( $config ) ? 8 : 4);

$res = $fb->api('/19292868552');
Tap::ok( is_array( $res ), 'response is an array');
Tap::is( $res['id'], '19292868552', 'returned the facebook id in the json response');
Tap::is( $res['name'], 'Facebook Developers', 'name is Facebook Developers');
Tap::is( $cache->{'graph/1/985eb366cf312423dec896705ba7e75f13f38812'}['response'], $res, 'cached value matches response');
if( ! is_array( $config ) ) exit;

$fb = new Facebook( $config, $persistence = new Container );
$res = $fb->api('/19292868552');

Tap::ok( is_array( $res ), 'response is an array');
Tap::is( $res['id'], '19292868552', 'returned the facebook id in the json response');
Tap::is( $res['name'], 'Facebook Developers', 'name is Facebook Developers');



$res = $fb->api('/' . $config['appId'] . '/accounts/test-users/');

if( ! $res['data'] ) {
    $fb->api('/' . $config['appId'] . '/accounts/test-users/?installed=true&permissions=read_stream,user_birthday,email', 'POST');
    $res = $fb->api('/' . $config['appId'] . '/accounts/test-users/');
}

//Tap::debug( $res );
$user = $res['data'][0];
if( ! $user['id'] ) throw new Exception('unable to create test user');

$fb->setAccessToken( $user['access_token'] );

$res = $fb->api('/me');


Tap::ok( ctype_digit( $res['id'] ), 'got back a user id from the /me api call');
Tap::debug( $res );

/*
Tap::debug($cache->all());
Tap::debug($persistence->all());

*/