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
        usleep(500000);
        print ".";
    }
    print "\n";
}

$extract_data = function( $result ){
    $rows = array();
    foreach( $result['rows'] as $row ){
        $key = isset( $row['id'] ) ? $row['id'] : NULL;
        if( $key !== NULL ){
            $rows[$key] = $row['value'];
        } else {
            $rows[] = $row['value'];
        }
    }
    return $rows;
};

Tap::plan(22);
try {    
    $cb = new Store\Couchbase(array( 'app'=> 'app-v.' . time(), 'rest'=>'http://127.0.0.1:5984/default/', 'socket'=>'127.0.0.1:11211'));
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
    
    $res = $cb->saveView('full', 'function(doc){ emit(doc._id, {foo: doc.foo, bazz: doc.bazz, amount: doc.amount, ts: doc.ts});}', '');
    Tap::ok( $res['ok'], 'created a full view');
    
    $res = $cb->saveView('nokey', 'function(doc){ emit(doc._id, null);}', '');
    Tap::ok( $res['ok'], 'created a view with no data result');

    $res = $cb->saveView('2keys', 'function(doc){ emit([doc._id, doc.ts], {foo: doc.foo, bazz: doc.bazz, amount: doc.amount, ts: doc.ts});}', '');
    Tap::ok( $res['ok'], 'created a view emitting two keys');
    
    $res = $cb->saveView('2keys', 'function(doc){ emit([doc._id, doc.ts], {foo: doc.foo, bazz: doc.bazz, amount: doc.amount, ts: doc.ts});}', '');
    Tap::ok( $res['ok'], 'created a view emitting two keys');
    
    
    $res = $cb->saveView('amount', 'function(doc){ emit(doc._id, doc.amount);}', '_sum');
    Tap::ok( $res['ok'], 'created a view summing the amount');
    
    waitforRebuild(10);
    $res = $cb->view('full', array('limit'=>20, 'full_set'=>'true'));
    $result_set = $extract_data( $res );
    Tap::ok( is_array( $res ), 'got back a view of the data');
    Tap::is($result_set,$rows, 'all the rows results match what we put in');
    Tap::is( $res['total_rows'], $i, 'total_rows matches how many rows we inserted');
    
    $res = $cb->view('full', array('limit'=>20, 'show_metadata'=>'1'));
    $result_set = $extract_data( $res );
    Tap::ok( is_array( $res ), 'got back a view with metadata');
    
    $ids = array_keys($result_set);
    
    Tap::cmp_ok( $ids, '===', array_keys( $rows ), 'internal ids match up');
    //Tap::debug($res );
    $res = $cb->deleteView('full');
    Tap::ok( $res['ok'], 'deleted the the view');
    
    
    $res = $cb->view('nokey', array('limit'=>20, 'full_set'=>'true'));
    $result_set = $extract_data( $res );
    Tap::ok( is_array( $res ), 'got back a view of the keys');
    Tap::is($result_set, array_fill_keys(array_keys($rows), array()), 'got back the results with empty arrays as placeholders');
    
    
    $keys = array_keys( $rows );
    $res = $cb->view('2keys', array('limit'=>20, 'full_set'=>'true', 'startkey'=>array($keys[0]), 'endkey'=>array($keys[1])));
    $result_set = $extract_data( $res );
    Tap::ok( is_array( $res ), 'got back a view of the data for 2 keys');
    //Tap::debug( $res );
    
    $res = $cb->view('amount');
    $result_set = $extract_data( $res );
    Tap::ok( is_array( $res ), 'got back a view of the data for summing an amount');
    Tap::is( $result_set[0], $total, 'total matches up');

    $res = $cb->deleteAllViews();
    Tap::ok( $res['ok'], 'deleted all the views');
    
    
    $cb = new Store\Couchbase(array( 'rest'=>'http://127.0.0.1:5984/default/', 'socket'=>'127.0.0.1:11211'));
    Tap::ok( $cb instanceof Store\Couchbase, 'instantiated couchbase without the app prefix');
    
    $res = $cb->saveView('test', 'function(doc){ emit(doc._id, doc); }');
    Tap::ok(is_array( $res ), 'created a view in the default design');
    $res = $cb->deleteView('test');
    Tap::ok( $res['ok'], 'deleted the the view');
   
    
    
    //Tap::debug( $res );

} catch( \Exception $e ){
    Tap::debug($e);
    print_R( $cb->http() );
    Tap::fail('fatal exception thrown');
}
