<?php
namespace Gaia\DB\Closure;
use Gaia\DB\Iface;
use Gaia\DB\Result;

class CI {

    public static function closures( \CI_DB_driver $db ){
        $_ = array();
        $lastquery = '';
        
        $_['__tostring'] = function () use ( $db, & $lastquery){
            $errmsg = $db->_error_message();
            $error = ( $errmsg ) ? $db->_error_number() . ': ' . $errmsg : '';
            $res ='(Gaia\DB\CI object - ' . "\n" .
                '  [driver] => ' . $db->dbdriver . "\n" .
                '  [connection] => ' . $db->hostname  . "\n" .
                '  [error] => ' . $error . "\n" .
                '  [lastquery] => ' . $lastquery . "\n" .
                ')';
            return $res;
        };
        
        $_['prep_args'] = $format_args = function($query, array $args ) use ( $db, & $lastquery ){
            if( ! $args || count( $args ) < 1 ) return $query;
            return \Gaia\DB\Query::prepare( 
                $query, 
                $args, 
                function($v) use( $db ){  return "'" . $db->escape_str( $v ) . "'"; }
               );
        };
        
         $_['execute'] = function( $query ) use ( $db, & $lastquery ){
            $lastquery = $query;
            if( strlen( $lastquery ) > 500 ) $lastquery = substr($lastquery, 0, 485) . ' ...[trucated]';
            try {
                $res = $db->query($query);
                
                if( ! $res ) return FALSE;
                $_ = array();
                
                if( is_object( $res ) ){
                    $_['fetch'] = function() use( $res ){
                        return $res->_fetch_assoc();
                    };
                    $_['free'] = function() use( $res ){
                        $res->free_result();
                    };
                }
                
                $_['affected'] = $db->affected_rows();
                if( preg_match('#^insert#i', $query )) $_['insertid'] = $db->insert_id();

                return new Result( $_ );
        
            } catch( Exception $e ){
                return FALSE;
            }
        };
                    
        $_['start'] = function ($auth = NULL) use ($db){ 
            if( $db instanceof Iface ) return $db->start($auth);
            $db->trans_start();
            return TRUE;
        };
        
        $_['commit'] = function ($auth = NULL) use ($db){ 
            if( $db instanceof Iface ) return $db->commit($auth);
            $db->trans_complete();
            return TRUE;
        };
        
        $_['rollback'] = function ($auth = NULL) use ($db){ 
            if( $db instanceof Iface ) return $db->rollback($auth);
            $db->trans_rollback($auth);
            return TRUE;
        };
        
        $_['error'] = function() use ( $db ){
            return $db->_error_message();
        };
        
        $_['errorcode'] = function() use ( $db ){
            return $db->_error_number();
        };
        
        $_['isa'] = function($name) use ( $db ){
            if( $db instanceof $name) return TRUE;
            if( $db->dbdriver == $name ) return TRUE;
            return FALSE;
        };
        
        $_['hash'] = function() use ( $db ){
            if( $db instanceof Iface ) return $db->hash();
            return spl_object_hash( $db );
        };
        
        return $_;
    }
}
