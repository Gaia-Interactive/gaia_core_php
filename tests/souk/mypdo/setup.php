<?php
require __DIR__ . '/../lib/setup.php';
use Gaia\DB;
use Gaia\Test\Tap;

if( ! class_exists('\PDO') ){
    Tap::plan('skip_all', 'pdo not installed');
}


if( ! in_array( 'mysql', PDO::getAvailableDrivers()) ){
    Tap::plan('skip_all', 'this version of PDO does not support mysql');
}

if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}


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