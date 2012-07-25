<?php
use Gaia\Skein;
use Gaia\Test\Tap;
use Gaia\Time;

Tap::plan(25);

Tap::ok( $skein instanceof Skein\Iface, 'created new skein object, implements expected interface');

$id = $skein->add( $data = array('foo'=>mt_rand(1, 100000000)) );

Tap::ok(ctype_digit($id), 'added data, got back an id');

Tap::is( $skein->get( $id ), $data, 'read back the data I stored');
Tap::is( $skein->get( array($id) ), array( $id=>$data), 'multi-get interface works too');

$data = array('foo'=>mt_rand(1, 100000000));

$skein->store( $id, $data );

Tap::is( $skein->get( $id ), $data, 'stored the data with new values, get returns what I wrote');

$batch = array( $id => $data );
for( $i = 0; $i < 10; $i++){
    Time::offset(86400 * 5 );
    $id = $skein->add(  $data = array('foo'=>mt_rand(1, 100000000)) );
    $batch[ $id ] = $data;
}

$ids = array_keys( $batch );

$res = $skein->get( $ids );

Tap::is( $res, $batch, 'added a bunch of keys and read them back using get( ids ) interface');

Tap::is( $skein->ids( array( 'limit' => 100 ) ), $ids, 'got the keys back in ascending order');



Tap::is( $res = $skein->ids( array( 'sort'=>'ascending', 'limit' => 5 ) ), array_slice($ids, 0, 5), 'got the keys back in ascending order, limit 5');

Tap::is( $res = $skein->ids( array( 'sort'=>'ascending', 'limit' => 5, 'start_after'=> $ids[5] ) ), array_slice($ids, 5, 5), 'got the keys back in ascending order, limit 5, starting with the 5th id');

Tap::is( $res = $skein->ids(array('limit'=>1)), array($ids[0]), 'with limit 1, got back the 1st id');



$ids = array_reverse( $ids );

Tap::is( $skein->ids( array('sort'=>'descending', 'limit'=>100) ), $ids, 'got the keys back in descending order');

Tap::is( $res = $skein->ids(  array('sort'=>'descending', 'limit'=>5) ), array_slice($ids, 0, 5), 'got the keys back in descending order, limit 5');

Tap::is( $res = $skein->ids(  array('sort'=>'descending', 'limit'=>5, 'start_after'=> $ids[5] ) ), array_slice($ids, 5, 5), 'got the keys back in descending order, limit 5, starting with the 5th id');

Tap::is( $res = $skein->ids(  array('sort'=>'descending', 'limit'=>1)), array($ids[0]), 'with limit 1, got back the last id');


Tap::is( $skein->count(), 11, 'count matches the number we added');


$ct = 0;
$sum = 0;
$cb = function( $id, $data ) use ( & $ct, & $sum, $batch ){
    if( $ct > 5 ) return FALSE;
    if( $batch[ $id ] != $data ) throw new Exception('invalid data');
    $sum = bcadd($sum, $data['foo']);
    $ct++;
};

$ct = 0;
$sum = 0;
$skein->filter( array('closure'=>$cb ) );

$expected_sum = 0;
foreach( array_slice($batch, 0, 6) as $data ) $expected_sum = bcadd($expected_sum, $data['foo']);
Tap::is( $ct, 6, 'filter ascending iterated the correct number of rows');
Tap::is( $sum, $expected_sum, 'sum from filter arrived at the correct amount' );


$ct = 0;
$sum = 0;
$ids = array_keys( $batch );

$skein->filter( array('sort'=>'ascending', 'closure'=>$cb, 'start_after'=>$ids[5] ) );

$expected_sum = 0;
foreach( array_slice( $batch, 5) as $data ) $expected_sum = bcadd($expected_sum, $data['foo']);
Tap::is( $ct, 6, 'filter ascending with start_after param iterated the correct number of rows');
Tap::is( $sum, $expected_sum, 'sum from filter with start_after arrived at the correct amount' );



$ct = 0;
$sum = 0;
$skein->filter( array('sort'=>'descending', 'closure'=>$cb ) );

$expected_sum = 0;
foreach( array_slice(array_reverse( $batch, TRUE ), 0, 6) as $data ) $expected_sum = bcadd($expected_sum, $data['foo']);
Tap::is( $ct, 6, 'filter descending iterated the correct number of rows');
Tap::is( $sum, $expected_sum, 'sum from filter arrived at the correct amount' );


$ct = 0;
$sum = 0;
$skein->filter( array('sort'=>'descending', 'closure'=>$cb, 'start_after'=>$ids[5] ) );

$expected_sum = 0;
foreach( array_slice(array_reverse( $batch, TRUE ), 5) as $data ) $expected_sum = bcadd($expected_sum, $data['foo']);
Tap::is( $ct, 6, 'filter descending with start_after iterated the correct number of rows');
Tap::is( $sum, $expected_sum, 'sum from filter with start_after arrived at the correct amount' );


$shard = mt_rand(1, 100);

$id = $skein->add( $data = array('foo'=>mt_rand(1, 1000000000)), $shard );

$parts = Skein\Util::parseId( $id );

Tap::is( $parts[0], $shard, 'created a new entry, using a custom shard');

Tap::is( $skein->get( $id ), $data, 'read back the entry with custom shard');
