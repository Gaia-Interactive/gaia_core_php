<?php
namespace Gaia\DB\Closure;
use Gaia\DB\Iface;
use Gaia\DB\Result;

class PDO {
    public static function closures( \PDO $db ){
        $_ = array();
        
        $lastquery = '';
        
         $_['__tostring'] = function () use ( $db, & $lastquery){
            list( $errstate, $errcode, $errmsg ) = $db->errorInfo();
            $error = ( $errmsg ) ? $errcode . ': ' . $errmsg : '';
            $res ='(Gaia\DB\PDO object - ' . "\n" .
                '  [driver] => ' . $db->getAttribute(\PDO::ATTR_DRIVER_NAME) . "\n" .
                '  [connection] => ' . @$db->getAttribute(\PDO::ATTR_CONNECTION_STATUS) . "\n" .
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
                function($v) use( $db ){ return $db->quote( $v ); }
               );
        };
        
         $_['execute'] = function( $query ) use ( $db, & $lastquery ){
            try {
                $lastquery = $query;
                if( strlen( $lastquery ) > 500 ) $lastquery = substr($lastquery, 0, 485) . ' ...[trucated]';
                $res = $db->query($query);
                
                if( ! $res ) return FALSE;
                $_ = array();
            
                $_['fetch'] = function() use( $res ){
                    return $res->fetch(\PDO::FETCH_ASSOC);
                };
                $_['free'] = function() use( $res ){
                    $res->closeCursor();
                };
                        
                $_['affected'] = $res->rowCount();
                $_['insertid'] = $db->lastInsertId();
                    
                return new Result( $_ );
        
            } catch( \Exception $e ){
                return FALSE;
            }
        };
                    
        $_['start'] = function ($auth = NULL) use ($db){ 
            if( $db instanceof Iface ) return $db->start($auth);
            return $db->beginTransaction();
        };
        
        $_['commit'] = function ($auth = NULL) use ($db){ 
            if( $db instanceof Iface ) return $db->commit($auth);
            return $db->commit();
        };
        
        $_['rollback'] = function ($auth = NULL) use ($db){
            if( $db instanceof Iface ) return $db->rollback($auth);
            return $db->rollback();
        };
        
        $_['error'] = function() use ( $db ){
            $info = $db->errorInfo();
            return $info[2];
        };
        
        $_['errorcode'] = function() use ( $db ){
            $info = $db->errorInfo();
            return $info[1];
        };
        
        $_['isa'] = function($name) use ( $db ){
            if ( $db instanceof $name) return TRUE;
            if( $name == $db->getAttribute(\PDO::ATTR_DRIVER_NAME) ) return TRUE;
            return FALSE;
        };

        return $_;
    }
}
