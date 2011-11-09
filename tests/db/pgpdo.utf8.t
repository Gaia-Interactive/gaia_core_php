#!/usr/bin/env php
<?php
include __DIR__ . '/../common.php';

use Gaia\Test\Tap;
use Gaia\DB;

include __DIR__ . '/../common.php';
include __DIR__ . '/../assert/pdo_installed.php';
include __DIR__ . '/../assert/pdo_pgsql_installed.php';
include __DIR__ . '/../assert/postgres_running.php';

try {
   $db = new Gaia\DB\Driver\PDO('pgsql:host=localhost;port=5432;dbname=test');
} catch( \Exception $e ){
    Tap::plan('skip_all', $e->__toString());
}

$raw = file_get_contents(__DIR__ . '/../sample/i_can_eat_glass.txt');


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