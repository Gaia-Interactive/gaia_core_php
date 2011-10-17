#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\NewID;
Tap::plan(4);

$new = new NewId\Callback( function(){
    static $ct;
    if( ! isset( $ct ) ) $ct = 0;
    return $ct++;
});
$id = $new->id();

Tap::ok( ctype_digit( $id ), 'id returned is a string of digits');

$ids = $new->ids(10);

Tap::ok( is_array( $ids ) && count( $ids ) == 10, 'ids returned a list of 10 items when I asked for 10');

$status = TRUE;

foreach( $ids as $id ){
    if( ! ctype_digit( $id ) ) $status = FALSE;
}

Tap::ok( $status, 'all of the ids are digits');

$id1 = $new->id();


$id2 = $new->id();

Tap::cmp_ok( $id1, '<', $id2, 'an id generated is larger than the first one');