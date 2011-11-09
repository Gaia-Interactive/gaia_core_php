<?php
use Gaia\DB;
use Gaia\Test\Tap;

include __DIR__ . '/../lib/setup.php';
include __DIR__ . '/../../assert/pdo_installed.php';
include __DIR__ . '/../../assert/pdo_mysql_installed.php';
include __DIR__ . '/../../assert/mysql_running.php';

DB\Connection::load( array(
    'test'=> function(){
         $db = new Gaia\DB\Driver\PDO('mysql:host=127.0.0.1;dbname=test;port=3306');
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