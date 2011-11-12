<?php
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\Time;

if( ! isset( $skip_expiration_tests ) ) $skip_expiration_tests = FALSE;
Tap::plan(24);

$data = array();
for( $i = 1; $i <= 3; $i++){
    $data[ 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000) ] = $i;
}

$res = FALSE;

$ret = array();
foreach( $data as $k => $v ) {
    if( $ret[ $k ] = $cache->get( $k ) ) $res = TRUE;
}

Tap::ok( ! $res, 'none of the data exists before I write it in the cache');
if( $res ) Tap::debug( $ret );

$res = TRUE;
$ret = array();
foreach( $data as $k => $v ){
    if( ! $ret[ $k ] = $cache->set( $k, $v, 10) ) $res = FALSE;
}
Tap::ok( $res, 'wrote all of my data into the cache');
if( ! $res ) Tap::debug( $ret );

$res = TRUE;
foreach( $data as $k => $v ){
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

if( $skip_expiration_tests || ! method_exists( $cache, 'ttlEnabled') || ! $cache->ttlEnabled() ){
    Tap::ok(TRUE, 'skipping expire test');
} else {
    Time::offset(11);    
    Tap::ok( $cache->add( $k, 1, 10), 'after expiration time, add works');
}
Tap::ok( $cache->replace( $k, 1, 10 ), 'replace works after the successful add');

Tap::ok( $cache->delete($k ), 'successfully deleted the key');

Tap::ok( ! $cache->replace( $k, 1, 10), 'replace fails after key deletion');
Tap::ok( $cache->add( $k, 1, 10), 'add works after key deletion');
Tap::ok( $cache->replace( $k, 1, 10), 'replace works after key is added');

$k = 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000);
Tap::ok( $cache->get( $k ) === NULL, 'cache get on a non-existent key returns NULL (not false)');

$k = 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000);

Tap::is( $cache->increment($k, 1), FALSE, 'increment a new key returns (bool) FALSE');
Tap::is( $cache->decrement($k, 1), FALSE, 'decrement a new key returns (bool) FALSE');

Tap::cmp_ok( $cache->set( $k, 'test' ), '===', TRUE, 'setting a key returns bool TRUE');
Tap::cmp_ok( $cache->replace( $k, 'test1' ), '===', TRUE, 'replacing a key returns bool TRUE');
Tap::cmp_ok( $cache->{$k} = '11', '===', '11', 'setting using the magic method property approach returns value');
unset( $cache->{$k} );
Tap::cmp_ok( $cache->add($k, 'fun'), '===', TRUE, 'adding a key returns (bool) TRUE');

Tap::is( $cache->set( $k, NULL ), TRUE, 'setting a key to null returns true');
Tap::cmp_ok( $cache->get( array( $k ) ), '===', array(), 'after setting the key to null, key is deleted');


Tap::is( $cache->set( $k, $v = '0' ), TRUE, 'setting a key to zero returns true');
Tap::cmp_ok( $cache->get( $k ), '===', $v, 'after setting the key to 0, get returns zero value');
Tap::cmp_ok( $cache->get( array( $k ) ), '===', array($k=>$v), 'multi-get returns the key with zero value');
