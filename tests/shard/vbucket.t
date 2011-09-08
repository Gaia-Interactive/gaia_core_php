#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Shard\VBucket;
Tap::plan(6);
$f = new VBucket(range(10, 20));
Tap::is( $f->shard( $id = 10), 20, "id $id maps to correct shard");
Tap::is( $f->shard( $id = 100), 10, "id $id maps to correct shard");
Tap::is( $f->shard( $id = 1000), 18, "id $id maps to correct shard");
Tap::is( $f->shard( $id = 1454309882), 11, "id $id maps to correct shard");
$f1 = new VBucket( $f->export() );
Tap::is( $f->export(), $f1->export(), 'importing/exporting into new object results in correct output');
$f2 = new VBucket( $f->export() );
Tap::is( $f->resolve(array(10, 100)), array(20=>array(10), 10=>array(100)), 'resolve the id list to a mapping of shards per id');
$f3 = new VBucket( range(0,999) );
var_dump( $f3 );