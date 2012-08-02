<?php
namespace Gaia\DB\Closure;
use Gaia\DB\Iface;
use Gaia\DB\Result;

class MySQLi {

    public static function closures( \MySQLi $db ){
    
        $_ = array();
        $lastquery = '';

        $_['__tostring'] = function() use ( $db, & $lastquery ) {
            if( $db->connect_error ){ 
                $error = ($db->connect_error) ? $db->connect_errno . ': connection error ... ' . $db->connect_error : '';
            } else {
                $error = ($db->error) ?  $db->errno . ': ' . $db->error : '';
            }
            @ $res ='(Gaia\DB\MySQLi object - ' . "\n" .
                '  [connection] => ' . $db->host_info . "\n" .
                '  [error] => ' . $error . "\n" .
                '  [lastquery] => ' . $lastquery . "\n" .
                ')';
            return $res;
        };
        
        $_['prep_args'] = $format_args = function($query, array $args ) use ( $db ){
            if( ! $args || count( $args ) < 1 ) return $query;
            return \Gaia\DB\Query::prepare( 
                $query, 
                $args, 
                function($v) use( $db ){ return "'" . $db->real_escape_string( $v ) . "'"; }
               );
        
        };
                
        $_['execute'] = function( $query ) use ( $db, & $lastquery ){
            $lastquery = $query;
            if( strlen( $lastquery ) > 500 ) $lastquery = substr($lastquery, 0, 485) . ' ...[trucated]';
            $res = $db->query( $query );
            if( ! $res ) return FALSE;
            $_ = array();
            
            if( is_object( $res ) ){
                $_['fetch'] = function() use( $res ){
                    return $res->fetch_assoc();
                };
                $_['free'] = function() use( $res ){
                    $res->free_result();
                };
            }
            $_['affected'] = $db->affected_rows;
            $_['insertid'] = $db->insert_id;
            
            return new Result( $_ );
        };
                    
        $_['start'] = function ($auth = NULL) use ( $db ){
            if( $db instanceof Iface ) return $db->start($auth);
            return $db->query('START TRANSACTION');
        };
        
        $_['rollback'] = function ($auth = NULL) use ( $db ){
            if( $db instanceof Iface ) return $db->rollback($auth);
            return $db->query('ROLLBACK');
        };
        
        $_['commit'] = function ($auth = NULL) use ( $db ){
            if( $db instanceof Iface ) return $db->commit($auth);
            return $db->query('COMMIT');
        };
        
        $_['error'] = function() use ( $db ){
            return $db->error;
        };
        
        $_['errorcode'] = function() use ( $db ){
            return $db->errno;
        };
        
        $_['isa'] = function($name) use ( $db ){
            if( $db instanceof $name) return TRUE;
            if( $name == 'mysql' ) return TRUE;
            return FALSE;
        };
        
        $_['hash'] = function() use ( $db ){
            if( $db instanceof Iface ) return $db->hash();
            return spl_object_hash( $db );
        };
        
        return $_;
    }
}