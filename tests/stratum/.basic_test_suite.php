<?php
use Gaia\Stratum;
use Gaia\Test\Tap;
use Gaia\Time;

if( ! isset( $use_int_keys ) )  $use_int_keys = FALSE;
if( ! isset( $use_bin_constraint ) )  $use_bin_constraint = FALSE;
if( ! isset( $plan ) ) $plan = 0;

Tap::plan($plan + 14);

Tap::ok( $stratum instanceof Stratum\Iface, 'stratum object instantiated with correct interface');

$pairs = array();
for( $i = 1; $i <= 10; $i++ ){
    $key = $use_int_keys ? strval($i + 100000000) : 'foo' . $i;
    do {
        $value = strval(mt_rand(1, 10000000));
    } while( in_array( $value, $pairs ) );
    $pairs[ $key ] = $value;
    $res = $stratum->store($key, $value);
}
$res = $stratum->query();

asort( $pairs, TRUE );

Tap::is( $res, $pairs, 'stored a bunch of pairs ... query matches what I stored');
//Tap::debug( $pairs );
//Tap::debug( $res );

Tap::is( $stratum->query(array('limit'=>'0,1')), array_slice($pairs, 0, 1, TRUE), 'queried the first in the list');
Tap::is( $stratum->query(array('limit'=>2, 'sort'=>'desc')), array_slice($pairs, -2, NULL, TRUE), 'queried the last two in the list');
$middle = array_slice($pairs, 5, 1, TRUE);
list( $key, $value ) = each($middle);
Tap::is( $stratum->query(array( 'min'=>$value, 'limit'=>2)), array_slice($pairs, 5, 2, TRUE), 'queried from the middle of the list');

Tap::is( $stratum->query(array( 'max'=>$value, 'limit'=>2, 'sort'=>'DESC')), array_reverse(array_slice($pairs, 4, 2, TRUE), TRUE), 'queried from the middle of the list in reverse');

Tap::is( $stratum->query(array('search'=>array_values(array_slice($pairs, 2, 3, TRUE) )  )), array_slice($pairs, 2,3, TRUE), 'search by value matches correct result set');

$expected = $pairs;
ksort( $expected );

$res = $stratum->batch();

Tap::is( count($res), count($pairs), 'batch call returns correct result count');


if( $use_bin_constraint ){
    $expected = $res;
    Tap::ok(TRUE, 'skipping first sort order because we cant do exact match of mysql binary ordering');
} else {
Tap::is( $res, $expected, 'batch call returns result set sorted by key');
}

Tap::is( $stratum->batch(array('limit'=>'0,1')), array_slice($expected, 0, 1, TRUE), 'batch the first in the list');

Tap::is( $stratum->batch(array('limit'=>2, 'sort'=>'desc')), array_slice(array_reverse($expected, TRUE), 0, 2, TRUE), 'batch the last two in the list');
Tap::is( $stratum->batch(array('start_after'=>min(array_keys($expected)))), array_slice($expected, 1, count( $expected ), TRUE), 'batch starting after the first one');
$expected = array_reverse( $expected, TRUE);


Tap::is( $stratum->batch(array('sort'=>'DESC')), $expected, 'batch call returns result set sorted by key and sort desc works');


Tap::ok( $stratum->delete( $key ), 'successfully deleted a constraint');


//Tap::debug($pairs);
