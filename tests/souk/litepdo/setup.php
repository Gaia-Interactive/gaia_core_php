<?php
use Gaia\DB;
use Gaia\Test\Tap;

include __DIR__ . '/../lib/setup.php';
include __DIR__ . '/../../assert/pdo_installed.php';
include __DIR__ . '/../../assert/pdo_sqlite_installed.php';

DB\Connection::load( array(
    'test'=> function(){
         $db = new DB\Driver\PDO( 'sqlite:/tmp/souk.db');
         $cb = array(
            'start'=> function(){ $i = \Gaia\DB\Transaction::internals(); Tap::debug('TXN: start ' . $i['depth']); },
            'commit'=> function(){ $i = \Gaia\DB\Transaction::internals(); Tap::debug('TXN: commit ' . $i['depth']); },
            'rollback'=> function(){ $i = \Gaia\DB\Transaction::internals(); Tap::debug('TXN: rollback ' . $i['depth']); },
            'query'=>function( $args ) {
                $query = array_shift( $args );
                $query = \Gaia\DB\Query::format($query, $args );
                Tap::debug( 'QUERY: ' . $query );
            },
         
         );
         //$db = new DB\Observe( $db, $cb);
         return $db;
    }
));