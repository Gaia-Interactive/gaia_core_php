#!/usr/bin/env php
<?php
use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/mysql_running.php';
include __DIR__ . '/../assert/ci_installed.php';

Tap::plan(153);

$raw = file_get_contents(__DIR__ . '/../sample/i_can_eat_glass.txt');

$db = new DB\Driver\CI( DB( array('dbdriver'=>'mysql','hostname'=>'127.0.0.1', 'database'=>'test') ) );

$lines = explode("\n", $raw);
$sql = "CREATE TEMPORARY TABLE t1utf8 (`i` INT UNSIGNED NOT NULL PRIMARY KEY, `line` VARCHAR(5000) ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8";
$db->execute($sql);

foreach($lines as $i=>$line ){
    $db->execute('INSERT INTO t1utf8 (`i`, `line`) VALUES (%i, %s)', $i, $line);
    $rs = $db->execute('SELECT %s AS `line`', $line );
    $row = $rs->row_array();
    $rs->free_result();
    Tap::cmp_ok($row['line'], '===', $line, 'sent to mysql and read it back: ' . $line );
}


$rs = $db->execute('SELECT * FROM t1utf8');
$mismatch = array();
foreach( $rs->result_array() as $row ){
    $readlines[ $row['i'] ] = $row['line'];
    if( $row['line'] !== $lines[ $row['i'] ] ) $mismatch[ $row['i'] ] = $row['line'];
}
$rs->free_result();
Tap::todo_starT();
Tap::cmp_ok( $mismatch, '===', array(), 'inserted all the rows and read them back, worked as expected');
//Tap::debug( $readlines );
Tap::todo_end();
$raw = file_get_contents(__DIR__ . '/../sample/UTF-8-test.txt');


$rs = $db->execute('SELECT %s AS `d`', $raw);
$row = $rs->row_array();
$rs->free_result();

Tap::cmp_ok( $row['d'], '===', $raw, 'passed a huge chunk of utf-8 data to mysql and asked for it back. got what I sent.');
//Tap::debug( $row['d'] );
