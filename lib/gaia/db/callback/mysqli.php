<?php
namespace Gaia\DB\Callback;
use Gaia\DB\Callback;

class MySQLi extends Callback implements \Gaia\DB\Iface {
    
    public function __construct(){
        $args = func_get_args();
        for( $i = 0; $i < 6; $i++) {
            if( ! isset( $args[ $i ] )) $args[$i] = NULL;
        }
        if( $args[0] instanceof \MySQLi ) {
            $mysqli = $args[0];
        } else {
            $mysqli = new \Mysqli( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        }
        
        $callbacks = array();
        $wrapper = $this;
        $callbacks['__tostring'] = function() use ( $mysqli ) {
            @ $res ='(Gaia\DB\MySQLi object - ' . "\n" .
                '  [affected_rows] => ' . $mysqli->affected_rows . "\n" .
                '  [client_info] => ' . $mysqli->client_info . "\n" .
                '  [client_version] => ' . $mysqli->client_version . "\n" .
                '  [connect_errno] => ' . $mysqli->connect_errno . "\n" .
                '  [connect_error] => ' . $mysqli->connect_error . "\n" .
                '  [errno] => ' . $mysqli->errno . "\n" .
                '  [error] => ' . $mysqli->error . "\n" .
                '  [field_count] => ' . $mysqli->field_count . "\n" .
                '  [host_info] => ' . $mysqli->host_info . "\n" .
                '  [info] => ' . $mysqli->info . "\n" .
                '  [insert_id] => ' . $mysqli->insert_id . "\n" .
                '  [server_info] => ' . $mysqli->server_info . "\n" .
                '  [server_version] => ' . $mysqli->server_version . "\n" .
                '  [sqlstate] => ' . $mysqli->sqlstate . "\n" .
                '  [protocol_version] => ' . $mysqli->protocol_version . "\n" .
                '  [thread_id] => ' . $mysqli->thread_id . "\n" .
                '  [warning_count] => ' . $mysqli->warning_count . "\n" .
                ')';
            return $res;
        };
        
        $callbacks['format_query_args'] = $format_args = function($query, array $args ) use ( $mysqli ){
            if( ! $args || count( $args ) < 1 ) return $query;
            return \Gaia\DB\Query::format( 
                $query, 
                $args, 
                function($v) use( $mysqli ){ return "'" . $mysqli->real_escape_string( $v ) . "'"; }
               );

        };
                
        $callbacks['execute'] = function( $query ) use ( $mysqli, $format_args ){
            $args = func_get_args();
            array_shift($args);
            return $mysqli->query( $format_args( $query, $args ) );
        };
        
        $callbacks['begin'] = function () use ( $mysqli ){
            return $mysqli->autocommit(FALSE);
        };
        
        
        $callbacks['rollback'] = function () use ( $mysqli ){
            return $mysqli->rollback();
        };
        
        $callbacks['commit'] = function () use ( $mysqli ){
            return $mysqli->commit();
        };
        
        $callbacks['__get'] = function ($k) use ( $mysqli ){
            return $mysqli->$k;
        };
        
        $callbacks['__call'] = function ($method, array $args ) use ( $mysqli ){
            return call_user_func_array( array( $mysqli, $method ), $args );
        };
                
        parent::__construct( $callbacks );
    }
}