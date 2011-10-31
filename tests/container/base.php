<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Container;
Tap::plan(22);
$c = new Container();
foreach( array('result_set', 'result_get', 'result_isset', 'result_unset') as $key ){
    $$key = array();
}
if( ! isset( $input ) || ! is_array( $input ) ) $input = array();
foreach( $input as $k=>$v ){
    $result_set[ $k ] = $c->$k = $v;
    $result_isset[ $k ] = isset( $c->$k );
    $result_get[ $k ] = $c->$k;
    unset( $c->$k );
    $result_unset[ $k ] = $c->$k;
}

Tap::is( $input, $result_set, 'set works properly' );
Tap::is( $input, $result_get, 'get works properly' );
Tap::is( array_fill_keys( array_keys( $input ), TRUE), $result_isset, 'isset works properly' );
Tap::is( array_fill_keys( array_keys( $input ), NULL), $result_unset, 'unset works properly' );
Tap::is( $c->non_existent, NULL, 'non-existent variables are null' );

$c->load( $input );
Tap::is( $c->get( array_keys($input) ), $input, 'multi-get works properly');
Tap::is( $c->all(), $input, 'grabbed all of the data at once');

$each = array();
while( list( $k, $v ) = $c->each()  ){
    $each[ $k ] = $v;
}
Tap::is( $c->all(), $each, 'each loop returns all the data in the container');
Tap::is( array_keys( $input ), $c->keys(), 'keys returns all the keys passed to input');

Tap::is( array_keys($input, 'a'), $c->keys('a'), 'search for a key');

Tap::is( $c->pop(), $v = array_pop($input), 'popped off an element, same as input');

Tap::is( $c->push($v), array_push($input, $v), 'pushed an element back onto the container');
Tap::is( $c->all(), $input, 'after pop and push, input matches container');

Tap::is( $c->shift(), $v = array_shift($input), 'shifted off an element, same as input');

Tap::is( $c->unshift($v), array_unshift($input, $v), 'unshift an element back onto the container');
Tap::is( $c->all(), $input, 'after shift and unshift, input matches container');
asort( $input );
$c->sort();
Tap::is( $c->all(), $input, 'after sorting, matches sorted input');
ksort( $input );
$c->ksort();
Tap::is( $c->all(), $input, 'after key sorting, matches sorted input');
krsort( $input );

Tap::is( $c->all(), $input, 'after reverse key sorting, matches sorted input');

$c->flush();
Tap::is( $c->all(), array(), 'flush removes everything from the container');
$c->load( $input );
Tap::is( $c->all(), $input, 'load puts it all back in again');

$c->push(0);
$c->push(NULL);
array_push( $input, 0);
array_push( $input, NULL);

Tap::is( $c->keys(NULL, TRUE), array_keys( $input, NULL, TRUE ), 'strict match works');
