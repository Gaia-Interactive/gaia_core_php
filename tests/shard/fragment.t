#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Shard\Fragment;
Tap::plan(7);
$f = new Fragment;
$f->populate( range(10, 20) );
Tap::is( $f->shard( $id = 10), 20, "id $id maps to correct shard");
Tap::is( $f->shard( $id = 100), 10, "id $id maps to correct shard");
Tap::is( $f->shard( $id = 1000), 18, "id $id maps to correct shard");
Tap::is( $f->shard( $id = 1454309882), 11, "id $id maps to correct shard");
$str = $f->exportString();
$f1 = new Fragment( $str );
Tap::is( $f->export(), $f1->export(), 'importing/exporting string into new object results in correct output');
$f2 = new Fragment( $f->export() );
Tap::is( $f->export(), $f2->export(), 'importing/exporting array into new object results in correct output');
Tap::is( $f->resolve(array(10, 100)), array(20=>array(10), 10=>array(100)), 'resolve the id list to a mapping of shards per id');
