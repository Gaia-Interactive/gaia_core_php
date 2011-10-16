<?php
use Gaia\Test\Tap;
use Gaia\Cache;

Tap::plan(12);

$data = array();
for( $i = 1; $i <= 3; $i++){
    $data[ 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000) ] = $i;
}

$res = FALSE;

foreach( $data as $k => $v ) {
    if( $cache->get( $k ) ) $res = TRUE;
}

Tap::ok( ! $res, 'none of the data exists before I write it in the cache');

$res = TRUE;
foreach( $data as $k => $v ){
    if( ! $cache->set( $k, $v, 10) ) $res = FALSE;
}
Tap::ok( $res, 'wrote all of my data into the cache');

$res = TRUE;
foreach( $data as $k => $v ){
//var_dump($cache->get( $k ));
   if(  $cache->get( $k ) != $v ) $res = FALSE;
}
Tap::ok( $res, 'checked each key and got back what I wrote');

$ret = $cache->get( array_keys( $data ) );
$res = TRUE;
foreach( $data as $k => $v ){
    if( $ret[ $k ] != $v ) $res = FALSE;
}
Tap::ok( $res, 'grabbed the keys all at once, got what I wrote');

$k = 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000);
Tap::ok( $cache->add( $k, 1, 10), 'adding a non-existent key');
Tap::ok( ! $cache->add( $k, 1, 10), 'second time, the add fails');

Cache\Mock::$time_offset += 11;

Tap::ok( $cache->add( $k, 1, 10), 'after expiration time, add works');

Tap::ok( $cache->replace( $k, 1, 10 ), 'replace works after the successful add');

Tap::ok( $cache->delete($k ), 'successfully deleted the key');

Tap::ok( ! $cache->replace( $k, 1, 10), 'replace fails after key deletion');
Tap::ok( $cache->add( $k, 1, 10), 'add works after key deletion');
Tap::ok( $cache->replace( $k, 1, 10), 'replace works after key is added');

