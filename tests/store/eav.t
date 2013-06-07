#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Store;

Tap::plan(14);

// utility function for instantiating the storage object 
function storage(){
    static $storage;
    if( ! isset( $storage ) ) $storage = new Store\KVP;
    return $storage;
}

$entity_id = sha1(uniqid());

$eav = new Store\EAV( storage(), $entity_id );
Tap::ok( $eav instanceof \Gaia\Store\Eav, 'instantiate the object');
$data = array('test1'=>mt_rand(1,100000), 'test2'=>mt_rand(1,100000), 'test3'=>mt_rand(1,100000));
foreach( $data as $k => $v ){
    $eav->$k = $v;
}
$eav->store();
$eav = new Store\EAV( storage(), $entity_id );
foreach( $data as $k => $v ){
    Tap::is( $eav->$k, $v, "found $k and it matches expected value");
}

$list = Store\EAV::bulkLoad( storage(), array( $entity_id, $empty_id = sha1(uniqid()) ) );

Tap::is( print_r($list[ $entity_id ]->all(), TRUE), print_r( $eav->all(), TRUE), 'bulkloading entity worked');
Tap::is( print_r($list[ $empty_id ]->all(), TRUE), print_r( array(), TRUE), 'bulkloading empty entity worked');

foreach( $eav->keys() as $k ){
    $eav->$k++;
}
$eav->store();
$eav = new Store\EAV( storage(), $entity_id );

foreach( $data as $k => $v ){
    Tap::is( $eav->$k, $v + 1, "incremented $k and the value went up by 1");
}

foreach( $eav->keys() as $k ){
    $eav->$k -= 2;
}
$eav->store();
$eav = new Store\EAV( storage(), $entity_id );

foreach( $data as $k => $v ){
    Tap::is( $eav->$k, $v -1, "decremented $k and the value went down");
}

Tap::is( $eav->count(), count( $data ), 'same number of elements in eav as we put in');

foreach( array_keys( $data ) as $k ){
    unset( $eav->$k );
}
$eav->store();

$eav = new Store\EAV( storage(), $entity_id );

Tap::is( $eav->count(), 0, 'after unsetting them all, eav count is zero');
