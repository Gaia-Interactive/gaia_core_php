#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\UTF8;
use Gaia\DB;

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'php-pdo not installed');
}

if( ! in_array( 'pgsql', PDO::getAvailableDrivers()) ){
    Tap::plan('skip_all', 'this version of PDO does not support postgres');
}

if( ! @fsockopen('localhost', 5432) ){
    Tap::plan('skip_all', 'postgres not running on localhost:5432');
}


$raw = file_get_contents(__DIR__ . '/../sample/i_can_eat_glass.txt');

if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}



$db = new Gaia\DB\Driver\PDO( 'pgsql:host=localhost;port=5432;dbname=test');

Tap::plan(152);
$lines = explode("\n", $raw);
$sql = "CREATE TEMPORARY TABLE t1utf8 (i INT NOT NULL PRIMARY KEY, line VARCHAR(5000) )";
$rs = $db->execute($sql);

foreach($lines as $i=>$line ){
    $db->execute('INSERT INTO t1utf8 (i, line) VALUES (%i, %s)', $i, $line);
    $rs = $db->execute('SELECT %s AS line', $line );
    $row = $rs->fetch(\PDO::FETCH_ASSOC);
    $rs->closeCursor();
    Tap::cmp_ok($row['line'], '===', $line, 'sent to db and read it back: ' . $line );
}


$rs = $db->execute('SELECT * FROM t1utf8');
$readlines = array();
while( $row = $rs->fetch(\PDO::FETCH_ASSOC) ){
    $readlines[ $row['i'] ] = $row['line'];
}
$rs->closeCursor();

Tap::cmp_ok( $readlines, '===', $lines, 'inserted all the rows and read them back, worked as expected');
//Tap::debug( $readlines );

Tap::debug('TODO: get more complex tests from utf-8 working');
/*
$raw = file_get_contents(__DIR__ . '/../sample/UTF-8-test.txt');

foreach(explode("\n", $raw) as $i=>$line ){
    $rs = $db->execute('SELECT %s AS line', $line );
    $row = $rs->fetch(\PDO::FETCH_ASSOC);
    $rs->closeCursor();
    Tap::cmp_ok($row['line'], '===', $line, 'sent to sqlite and read it back: ' . $line );

}
*/