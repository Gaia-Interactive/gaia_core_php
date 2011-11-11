<?php
namespace Gaia\DB\Callback;
use Gaia\DB\Callback;
use Gaia\DB\Transaction;

class PDO extends Callback implements \Gaia\DB\Iface {

    public function __construct( $dsn ){
        $args = func_get_args();
        
        $args = func_get_args();
        for( $i = 0; $i < 4; $i++) {
            if( ! isset( $args[ $i ] )) $args[$i] = NULL;
        }
        if( $args[0] instanceof \PDO ) {
            $db = $args[0];
        } else {
            $db = new \PDO( $args[0], $args[1], $args[2], $args[3]);
        }        
        $callbacks = array();
        $wrapper = $this;
        $db->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Gaia\DB\Driver\PDOStatement', array($wrapper)));
        $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT );
        
        $callbacks['__tostring'] = function () use ( $db ){
            list( $errstate, $errcode, $errmsg ) = $db->errorInfo();
            @ $res ='(Gaia\DB\PDO object - ' . "\n" .
                '  [driver] => ' . $db->getAttribute(\PDO::ATTR_DRIVER_NAME) . "\n" .
                '  [connection] => ' . $db->getAttribute(\PDO::ATTR_CONNECTION_STATUS) . "\n" .
                '  [version] => ' . $db->getAttribute(\PDO::ATTR_SERVER_VERSION) . "\n" .
                '  [info] => ' . $db->getAttribute(\PDO::ATTR_SERVER_INFO) . "\n" .
                '  [error] => ' . $errmsg . "\n" .
                '  [insert_id] => ' . $db->lastInsertId() . "\n" .
                ')';
            return $res;
        };
        
        $callbacks['prep_query_args'] = $prep_args = function($query, array $args ) use ( $db ){
            if( ! $args || count( $args ) < 1 ) return $query;
            return \Gaia\DB\Query::prepare( 
                $query, 
                $args, 
                function($v) use( $db ){ return $db->quote( $v ); }
               );
        };
        
        $callbacks['isa'] = function( $name ) use( $wrapper, $db ) {
            if( $wrapper instanceof $name ) return TRUE;
            if( $db instanceof $name ) return TRUE;
            $name = strtolower( $name );
            if( $name == 'pdo' ) return TRUE;
            return FALSE;
        };
        
         $callbacks['execute'] = function( $query ) use ( $db, $prep_args ){
            $args = func_get_args();
            array_shift($args);
            $rs = $db->query( $sql = $prep_args( $query, $args ) );
            return $rs;
        };
        
        $callbacks['query'] = function ( $query ) use ($db, $wrapper){
            if( $wrapper->lock ) return FALSE;
            $args = func_get_args();
            $res = call_user_func_array( array($db, 'query'), $args);
            if( $res ) return $res;
            if( $wrapper->txn ) {
                Transaction::block();
                $wrapper->lock = TRUE;
            }
            return $res;
        };
        
        $callbacks['exec'] = function ( $query ) use ($db, $wrapper){
            if( $wrapper->lock ) return FALSE;
            $res = $db->exec( $query );
            if( $res ) return $res;
            if( $wrapper->txn ) {
                Transaction::block();
                $wrapper->lock = TRUE;
            }
            return $res;
        };
        
        $callbacks['start'] = function () use ($db){ 
            return $db->beginTransaction();
        };
        
        $callbacks['beginTransaction'] = function () use( $wrapper ){
            return $wrapper->start();
        };
        
        $callbacks['commit'] = function () use ($db){ 
            return $db->commit();
        };
        
        $callbacks['rollback'] = function () use ($db){ 
            return $db->rollback();
        };
        
        $callbacks['__get'] = function ($k) use ( $db ){
            return $db->$k;
        };
        
        $callbacks['__call'] = function ($method, array $args ) use ( $db ){
            return call_user_func_array( array( $db, $method ), $args );
        };
        
        parent::__construct( $callbacks );
        
    }
}