#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysqli_installed.php';
include __DIR__ . '/../assert/mysql_running.php';

$raw = file_get_contents(__DIR__ . '/../sample/i_can_eat_glass.txt');

if( strlen( $raw ) < 1 ){
    Tap::plan('skip_all', 'unable to load test data');
}



$db = new DB\Driver\MySQLi('127.0.0.1', NULL, NULL, 'test', '3306');
$db->execute("SET NAMES 'utf8'");
Tap::plan(153);
$lines = explode("\n", $raw);
$sql = "CREATE TEMPORARY TABLE t1utf8 (`i` INT UNSIGNED NOT NULL PRIMARY KEY, `line` VARCHAR(5000) ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8";
$db->query($sql);

foreach($lines as $i=>$line ){
    $db->execute('INSERT INTO t1utf8 (`i`, `line`) VALUES (%i, %s)', $i, $line);
    $rs = $db->execute('SELECT %s AS `line`', $line );
    $row = $rs->fetch_assoc();
    $rs->free_result();
    Tap::cmp_ok($row['line'], '===', $line, 'sent to mysql and read it back: ' . $line );
}


$rs = $db->execute('SELECT * FROM t1utf8');
$readlines = array();
while( $row = $rs->fetch_assoc() ){
    $readlines[ $row['i'] ] = $row['line'];
}
$rs->free_result();

Tap::ok( $cmp = ($readlines === $lines), 'inserted all the rows and read them back, worked as expected');
if( ! $cmp ){
    $diff = array();
    foreach( $readlines as $i => $line ){
        if( $line !== $lines[ $i ] ) Tap::debug("line $i:\n> $line\n< " . $lines[ $i ]);
    }
}

$raw = file_get_contents(__DIR__ . '/../sample/UTF-8-test.txt');


$rs = $db->execute('SELECT %s AS `d`', $raw);
$row = $rs->fetch_assoc();
$rs->free_result();

Tap::cmp_ok( $row['d'], '===', $raw, 'passed a huge chunk of utf-8 data to mysql and asked for it back. got what I sent.');
//Tap::debug( $row['d'] );
