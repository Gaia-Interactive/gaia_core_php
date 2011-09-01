<?php

namespace Gaia\DB;

class XA {

    static $map = array();
    
    public static function start( $name ){
        if( isset( self::$map[ $name ] ) ) return FALSE;
        $db = Connection::instance($name);
        $res = $db->execute('XA START %s', self::xid() );
        if( ! $res ) $db->lock();
        return self::$map[$name ] = $res;
    }
    
    public static function commit(){
        $result = TRUE;
        
        foreach( self::$map as $name => $status ){
            if( ! $status ) $result = FALSE;
        }
        if( ! $result ){
            self::rollback();
            return FALSE;
        }
        
        $xid = self::xid();
        
        foreach( self::$map as $name => $status )
            $result = Connection::instance($name)->execute('XA END %s', $xid );
            if( ! $result ){
                self::rollback();
                return FALSE;
            }
            $result = Connection::instance($name)->execute('XA PREPARE %s', $xid );
            if( ! $result ){
                self::rollback();
                return FALSE;
            }
        }
        
        foreach( self::$map as $name => $status )
            $result = Connection::instance($name)->execute('XA COMMIT %s', $xid );
            if( ! $result ){
                self::rollback();
                return FALSE;
            }
        }
        
        
        return $result;
    }
    
    public static function xid(){
        return 'test';
    }
    
    
    public static function rollback(){
        $result = TRUE;
        foreach( self::$map as $name => $status ){
            if( ! $status ) continue;
            if( Connection::instance($name)->execute('XA ROLLBACK %s', $xid )) {
                self::$map[ $xid ] = FALSE;
            } else {
                $result = FALSE;
            }
        }
        return $result;
    }


}