<?php
use Gaia\Test\Tap;

if( ! isset( $db ) ) die("\n do not access this file directly\n");

$db = new Gaia\DB( $db );
Tap::debug( $db, 'db object' );


$rs = $db->execute('select 1 as test UNION select 2 as test');

Tap::debug( $rs, 'result object' );

Tap::debug( $rs->affected(), 'affected rows');

$rows = array();
while( $row = $rs->fetch() ) $rows[] = $row;

Tap::debug( $rows, 'result set' );