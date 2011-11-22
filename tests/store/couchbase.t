#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/memcache_installed.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/couchbase_running.php';

Tap::plan(10);

$view = 'test' . time();

$cb = new Store\Couchbase('dev_v1' . time(), 'http://127.0.0.1:5984/default/', '127.0.0.1:11211');
Tap::ok( $cb instanceof Store\Couchbase, 'instantiated couchbase');

$res = $cb->createView($view, 'function(doc){ emit(doc._id, doc);}', '');
Tap::ok( $res['ok'], 'created a simple view');
sleep(1);
//Tap::debug( $res );

$status = TRUE;
$rows = array();
for( $i = 0; $i < 3; $i++){
    $res = $cb->set( $key = 'fun.' . microtime(TRUE), $row = array('foo'=>'bar', 'bazz'=>'quux', 'checksum'=>md5(microtime(TRUE))), 3600 );
    $rows[ $key ] = $cb->get( $key );
    if( ! $res ) $status = FALSE;
}
Tap::ok( $status, 'set all the data in without problems');
sleep(1);
$res = $cb->getView($view, array('limit'=>20, 'full_set'=>'true'));
Tap::ok( is_array( $res ), 'got back a view of the data');
Tap::is($res['rows'],$rows, 'all the rows results match what we put in');
Tap::is( $res['total_rows'], $i, 'total_rows matches how many rows we inserted');

$res = $cb->getView($view, array('limit'=>20, 'show_metadata'=>'1'));
Tap::ok( is_array( $res ), 'got back a view with metadata');

$ids = array();
foreach( $res['rows'] as $row ){
    $ids[] = $row['_id'];
}
Tap::cmp_ok( $ids, '===', array_keys( $rows ), 'internal ids match up');
//Tap::debug($res );
$res = $cb->deleteView($view);
Tap::ok( $res['ok'], 'deleted the the view');
$res = $cb->deleteAllViews();
Tap::ok( $res['ok'], 'deleted all the views');


//Tap::debug( $res );