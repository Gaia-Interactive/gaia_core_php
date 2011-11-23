#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\Store;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/memcache_installed.php';
include __DIR__ . '/../assert/curl_installed.php';
include __DIR__ . '/../assert/couchbase_running.php';
include __DIR__ . '/../assert/memcache_running.php';


function waitforRebuild($iterations = 10 ){
    print "# waiting for view rebuild ";
    for( $i = 0; $i < $iterations; $i++){
        usleep(100000);
        print ".";
    }
    print "\n";
}

Tap::plan(19);
try {    
    $cb = new Store\Couchbase(array( 'app'=> 'dev_v' . time(), 'rest'=>'http://127.0.0.1:5984/default/', 'core'=>'127.0.0.1:11211'));
    Tap::ok( $cb instanceof Store\Couchbase, 'instantiated couchbase');
    
    //Tap::debug( $res );
    
    $status = TRUE;
    $rows = array();
    $total = 0;
    for( $i = 0; $i < 3; $i++){
        $amount = mt_rand(1, 100);
        $total += $amount;
        $res = $cb->set( $key = 'key' .( $i + 1 ), $row = array('foo'=>'bar', 'bazz'=>'quux', 'amount'=>$amount,  'ts'=>(string) microtime(TRUE)), 3600 );
        $rows[ $key ] = $cb->get( $key );
        if( ! $res ) $status = FALSE;
    }
    Tap::ok( $status, 'set all the data in without problems');
    
    $res = $cb->saveView('full', 'function(doc){ emit(doc._id, doc);}', '');
    Tap::ok( $res['ok'], 'created a full view');
    
    $res = $cb->saveView('nokey', 'function(doc){ emit(doc._id, null);}', '');
    Tap::ok( $res['ok'], 'created a view with no data result');

    $res = $cb->saveView('2keys', 'function(doc){ emit([doc._id, doc.ts], doc);}', '');
    Tap::ok( $res['ok'], 'created a view emitting two keys');
    
    $res = $cb->saveView('2keys', 'function(doc){ emit([doc._id, doc.ts], doc);}', '');
    Tap::ok( $res['ok'], 'created a view emitting two keys');
    
    
    $res = $cb->saveView('amount', 'function(doc){ emit(doc._id, doc.amount);}', '_sum');
    Tap::ok( $res['ok'], 'created a view summing the amount');
    
    waitforRebuild(10);
    $res = $cb->preview('full', array('limit'=>20, 'full_set'=>'true'));
    Tap::ok( is_array( $res ), 'got back a view of the data');
    Tap::is($res['rows'],$rows, 'all the rows results match what we put in');
    Tap::is( $res['total_rows'], $i, 'total_rows matches how many rows we inserted');
    
    $res = $cb->preview('full', array('limit'=>20, 'show_metadata'=>'1'));
    Tap::ok( is_array( $res ), 'got back a view with metadata');
    
    $ids = array_keys($res['rows']);
    
    Tap::cmp_ok( $ids, '===', array_keys( $rows ), 'internal ids match up');
    //Tap::debug($res );
    $res = $cb->deleteView('full');
    Tap::ok( $res['ok'], 'deleted the the view');
    
    
    $res = $cb->preview('nokey', array('limit'=>20, 'full_set'=>'true'));
    Tap::ok( is_array( $res ), 'got back a view of the keys');
    Tap::is($res['rows'], array_fill_keys(array_keys($rows), array()), 'got back the results with empty arrays as placeholders');
    
    
    $keys = array_keys( $rows );
    $res = $cb->preview('2keys', array('limit'=>20, 'full_set'=>'true', 'startkey'=>array($keys[0]), 'endkey'=>array($keys[1])));
    Tap::ok( is_array( $res ), 'got back a view of the data for 2 keys');
    //Tap::debug( $res );
    
    $res = $cb->preview('amount');
    Tap::ok( is_array( $res ), 'got back a view of the data for summing an amount');
    Tap::is( $res['rows'][0], $total, 'total matches up');

    $res = $cb->deleteAllViews();
    Tap::ok( $res['ok'], 'deleted all the views');
    
    
    //Tap::debug( $res );

} catch( \Exception $e ){
    Tap::debug($e);
    print_R( $cb->http() );
    Tap::fail('fatal exception thrown');
}
