#!/usr/bin/env php
<?php
require __DIR__ . '/../common.php';
require  __DIR__ . '/../../vendor/facebook/php-sdk/src/facebook.php';

use Gaia\Test\Tap;
use Gaia\DB;

class NoAuthFacebook extends BaseFacebook {

  protected function setPersistentData($key, $value){ return false; }
  protected function getPersistentData($key, $default = false){ return false; }
  protected function clearPersistentData($key){ return false; }
  protected function clearAllPersistentData(){ return false; }
  protected function _oauthRequest($url, $params) {
    foreach ($params as $key => $value) {
      if (!is_string($value)) $params[$key] = json_encode($value);
    }

    return $this->makeRequest($url, $params);
    }

}

$config = @include __DIR__ . '/facebook.config.php';
$cache = new Gaia\Cache\Memcache();
$cache->addServer('127.0.0.1', '11211');
if( is_array( $config ) ) {
    Gaia\Cache\Session::init( $cache );
    session_start();
}

try {
    
    $fb = new Gaia\Cache\Facebook( new NoAuthFacebook(array()), $cache );
} catch( Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}
Tap::plan(is_array( $config ) ? 6 : 3);

$res = $fb->api('/19292868552');
Tap::ok( is_array( $res ), 'response is an array');
Tap::is( $res['id'], '19292868552', 'returned the facebook id in the json response');
Tap::is( $res['name'], 'Facebook Platform', 'name is facebook platform');

if( ! is_array( $config ) ) exit;

$fb = new Gaia\Cache\Facebook( new Facebook( $config ), $cache );
$res = $fb->api('/19292868552');

Tap::ok( is_array( $res ), 'response is an array');
Tap::is( $res['id'], '19292868552', 'returned the facebook id in the json response');
Tap::is( $res['name'], 'Facebook Platform', 'name is facebook platform');


//Tap::Debug($res);
