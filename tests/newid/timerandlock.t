#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\NewID;
use Gaia\Cache;
Tap::plan(4);


// on production should always use memcache pointing at a global pool of cache servers
// consistent with all the webservers in the farm, but for this test, I can cheat and use the mock
// since it only applies to this local test and the interface is the same.
$cache = new Cache\Namespaced( new Cache\Mock, 'test');


$new = new NewId\TimeRandLock( $cache );
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

NewId\TimeRand::$time_offset++;

$id2 = $new->id();

Tap::cmp_ok( $id1, '<', $id2, 'an id generated a second later is larger than the first one');