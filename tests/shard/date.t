#!/usr/bin/env php
<?php
include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\Shard;
Tap::plan(8);


$d = new Shard\Date(array('by'=>'month', 'cutoff'=>365, 'start'=>strtotime('2011/01/01 00:00:00')));

$actual = array();
do { $actual[] = $d->shard(); } while( $d->next() );
$expected = array(
"201101",
"201012",
"201011",
"201010",
"201009",
"201008",
"201007",
"201006",
"201005",
"201004",
"201003",
"201002",
"201001",
);

Tap::is( $actual, $expected, 'month sharding works correctly');

$d->setShard('201002');
Tap::is( $d->shard(), '201002', 'setting to a specific month shard');

$d = new Shard\Date(array('by'=>'day', 'cutoff'=>5, 'start'=>strtotime('2011/01/01 00:00:00')));
$actual = array();
do { $actual[] = $d->shard(); } while( $d->next() );

$expected = array(
    "20110101",
    "20101231",
    "20101230",
    "20101229",
    "20101228",
    "20101227",
);

Tap::is( $actual, $expected, 'day sharding works correctly');

$d->setShard('20101229');
Tap::is( $d->shard(), '20101229', 'setting to a specific day shard');


$d = new Shard\Date(array('by'=>'week', 'cutoff'=>30, 'start'=>strtotime('2011/01/01 00:00:00')));
$actual = array();
do { $actual[] = $d->shard(); } while( $d->next() );

$expected = array(
    '201100',
    '201052',
    '201051',
    '201050',
    '201049',
);

Tap::is( $actual, $expected, 'week sharding works correctly');

$d->setShard('201052');
Tap::is( $d->shard(), '201052', 'setting to a specific week shard');

$d = new Shard\Date(array('by'=>'day', 'cutoff'=>5));

Tap::is( $d->shard(), date('Ymd'), 'when no start is specified, current timestamp is set');

$d = new Shard\Date(array('by'=>'week', 'cutoff'=>30, 'timestamp'=>strtotime('2020/01/01 00:00:00')));

Tap::is( $d->shard(), '202000', 'timestamp works into the future');



