<?php
use Gaia\Test\Tap;
use Gaia\Store;
use Gaia\Time;

if( ! isset( $skip_expiration_tests ) ) $skip_expiration_tests = FALSE;
Tap::plan(40);

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


Tap::ok( $cache->set( $k, 1, $ttl = (3600 * 24 * 30)), 'setting with a huge timeout');
Tap::cmp_ok( strval($cache->get( $k )), '===', '1', 'get returns correct value');

$incr = 1000000;


Tap::ok( $cache->increment( $k, $incr), 'incrementing with a large number');
Tap::cmp_ok( strval($cache->get( $k )), '===', strval($incr + 1), 'get returns correct value');

Tap::ok( $cache->decrement( $k, $incr), 'decrementing with a large number');
Tap::cmp_ok( intval($cache->get( $k )), '===', 1, 'get returns correct value');

$huge_number = 9223372036854775806;

if( ! is_int( $huge_number ) ) $huge_number = 2147483646;

Tap::Debug( "testing with $huge_number" );

Tap::ok( $cache->set( $k, $v = $huge_number), 'setting a huge number');
Tap::cmp_ok( strval($cache->get( $k )), '===', strval($v), 'get returns correct value');

$v = $v + 1;
Tap::cmp_ok( strval($cache->increment($k, 1)), '===', strval($v),  'increment a huge number by 1');
Tap::cmp_ok( strval($cache->get( $k )), '===', strval( $v ), 'get returns correct value');

$cache->set( $k, $v);

$v = $v - 1;
Tap::cmp_ok( strval($cache->decrement($k, 1)), '===', strval($v),  'decrement a huge number by 1');
Tap::cmp_ok( strval($cache->get( $k )), '===', strval( $v ), 'get returns correct value');

$k = 'gaia/cache/test/' . microtime(TRUE) . '/' . mt_rand(1, 10000);
$v = '我能吞下玻璃而不傷身體';
Tap::ok( $cache->set( $k, $v), 'setting a string with utf-8 chars in it');
Tap::cmp_ok( strval($cache->get( $k )), '===',  $v, 'get returns correct value');

Tap::ok( $cache->delete( $k ), 'deleting the key');
Tap::cmp_ok( $cache->get( $k ), '===',  NULL, 'after deleting, get returns NULL');
