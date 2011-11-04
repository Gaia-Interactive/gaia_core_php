#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;
use Gaia\DB;

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}

if( ! in_array( 'mysql', PDO::getAvailableDrivers()) ){
    Tap::plan('skip_all', 'this version of PDO does not support mysql');
}

if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}

$raw = file_get_contents(__DIR__ . '/../sample/i_can_eat_glass.txt');

if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}



$db = new DB\Driver\PDO('mysql:host=127.0.0.1;dbname=test;port=3306');

Tap::plan(153);
$lines = explode("\n", $raw);
$sql = "CREATE TEMPORARY TABLE t1utf8 (`i` INT UNSIGNED NOT NULL PRIMARY KEY, `line` VARCHAR(5000) ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8";
$db->execute($sql);

foreach($lines as $i=>$line ){
    $db->execute('INSERT INTO t1utf8 (`i`, `line`) VALUES (%i, %s)', $i, $line);
    $rs = $db->execute('SELECT %s AS `line`', $line );
    $row = $rs->fetch(\PDO::FETCH_ASSOC);
    $rs->closeCursor();
    Tap::cmp_ok($row['line'], '===', $line, 'sent to mysql and read it back: ' . $line );
}


$rs = $db->execute('SELECT * FROM t1utf8');
$readlines = array();
while( $row = $rs->fetch(\PDO::FETCH_ASSOC) ){
    $readlines[ $row['i'] ] = $row['line'];
}
$rs->closeCursor();

Tap::cmp_ok( $readlines, '===', $lines, 'inserted all the rows and read them back, worked as expected');
//Tap::debug( $readlines );

$raw = file_get_contents(__DIR__ . '/../sample/UTF-8-test.txt');


$rs = $db->execute('SELECT %s AS `d`', $raw);
$row = $rs->fetch(\PDO::FETCH_ASSOC);
$rs->closeCursor();

Tap::cmp_ok( $row['d'], '===', $raw, 'passed a huge chunk of utf-8 data to mysql and asked for it back. got what I sent.');
//Tap::debug( $row['d'] );
