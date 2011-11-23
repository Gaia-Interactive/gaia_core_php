#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
include __DIR__ . '/../../tests/assert/memcache_installed.php';
include __DIR__ . '/../../tests/assert/curl_installed.php';
include __DIR__ . '/../../tests/assert/couchbase_running.php';
include __DIR__ . '/../../tests/assert/memcache_running.php';

use Gaia\Store;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';


$cb = new Store\Couchbase( array(
      'app'       => 'dev_jloehrer-zoo',
      'rest'      => 'http://127.0.0.1:5984/default/',
      'socket'    => '127.0.0.1:11211',
));




$cb->saveView('mammals', 'function(doc){ if( doc.type=="mammal" ){ emit(doc._id, doc); }}');

$http = new \Gaia\Http\Request("http://192.168.96.128:5984/default/_design/dev_jloehrer-zoo/");
$result = json_decode( $http->exec()->body, TRUE);
Tap::debug( $result['views']['mammals']['map'] );

$cb->set('bear', array('type'=>'mammal', 'eats'=>'meat', 'legs'=>4 ) );

Tap::debug( $cb->get('bear') );

Tap::debug($cb->view('mammals', array('full_set'=>'true')));