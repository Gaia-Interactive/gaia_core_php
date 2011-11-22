#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/memcache_installed.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/couchbase_running.php';
include __DIR__ . '/../assert/memcache_running.php';


function waitforRebuild(){
    print "# waiting for rebuild ";
    for( $i = 0; $i < 10; $i++){
        usleep(100000);
        print ".";
    }
    print "\n";
}

Tap::plan(15);
try {

    $view = 'test' . time() . '-' . mt_rand(1, 100000);
    
    $cb = new Store\Couchbase(array( 'app'=> 'dev_v' . time(), 'rest'=>'http://127.0.0.1:5984/default/', 'core'=>'127.0.0.1:11211'));
    Tap::ok( $cb instanceof Store\Couchbase, 'instantiated couchbase');
    
    $res = $cb->saveView($view, 'function(doc){ emit(doc._id, doc);}', '');
    Tap::ok( $res['ok'], 'created a simple view');
    waitforRebuild();
    //Tap::debug( $res );
    
    $status = TRUE;
    $rows = array();
    for( $i = 0; $i < 3; $i++){
        $res = $cb->set( $key = 'key' .( $i + 1 ), $row = array('foo'=>'bar', 'bazz'=>'quux', 'checksum'=>md5(microtime(TRUE))), 3600 );
        $rows[ $key ] = $cb->get( $key );
        if( ! $res ) $status = FALSE;
    }
    Tap::ok( $status, 'set all the data in without problems');
    waitforRebuild();
    $res = $cb->view($view, array('limit'=>20, 'full_set'=>'true'));
    Tap::ok( is_array( $res ), 'got back a view of the data');
    Tap::is($res['rows'],$rows, 'all the rows results match what we put in');
    Tap::is( $res['total_rows'], $i, 'total_rows matches how many rows we inserted');
    
    $res = $cb->view($view, array('limit'=>20, 'show_metadata'=>'1'));
    Tap::ok( is_array( $res ), 'got back a view with metadata');
    
    $ids = array();
    foreach( $res['rows'] as $row ){
        $ids[] = $row['_id'];
    }
    Tap::cmp_ok( $ids, '===', array_keys( $rows ), 'internal ids match up');
    //Tap::debug($res );
    $res = $cb->deleteView($view);
    Tap::ok( $res['ok'], 'deleted the the view');
    
    
    $view = 'test' . time() . '-' . mt_rand(1, 100000);
    $res = $cb->saveView($view, 'function(doc){ emit(doc._id, null);}', '');
    Tap::ok( $res['ok'], 'created a view with no data result');
    waitforRebuild();
    
    
    $res = $cb->view($view, array('limit'=>20, 'full_set'=>'true'));
    Tap::ok( is_array( $res ), 'got back a view of the data');
    Tap::is($res['rows'], array_fill_keys(array_keys($rows), array()), 'got back the results with empty arrays as placeholders');
    
    $view = 'test' . time() . '-' . mt_rand(1, 100000);
    $res = $cb->saveView($view, 'function(doc){ emit([doc._id, doc.checksum], doc);}', '');
    Tap::ok( $res['ok'], 'created a view emitting two keys');
    waitforRebuild();
    
    
    $keys = array_keys( $rows );
    $res = $cb->view($view, array('limit'=>20, 'full_set'=>'true', 'startkey'=>array($keys[0]), 'endkey'=>array($keys[1])));
    Tap::ok( is_array( $res ), 'got back a view of the data');
    //Tap::debug( $res );
    
    $res = $cb->deleteAllViews();
    Tap::ok( $res['ok'], 'deleted all the views');
    
    
    //Tap::debug( $res );

} catch( \Exception $e ){
    Tap::debug($e);
    print_R( $cb->http() );
    Tap::fail('fatal exception thrown');
}
