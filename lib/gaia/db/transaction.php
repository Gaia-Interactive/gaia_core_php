<?php

namespace Gaia\DB;

class Transaction
{
    protected static $tran = array();
    protected static $at_start = TRUE;
    protected static $commit_callbacks = array();
    protected static $rollback_callbacks = array();
    
    function add( Transaction_Iface $obj ){
        self::claimStart();
        if( ! $obj->begin( function (){ Transaction::block(); }) ) {
            return FALSE;
        }
        return self::$tran[spl_object_hash($obj)] = $obj;
    }
    
    public static function connections(){
        return self::$tran;
    }
    
    public static function instance( $name ){
        if (isset( self::$tran[$name] ) ) return self::$tran[$name];
        self::claimStart();
        $obj = Connection::get( $name );
        if( ! $obj instanceof Transaction_Iface ){
            throw new Exception('invalid object', $obj );
        }
        if( ! $obj->begin( function (){ Transaction::block(); }) ) {
            return FALSE;
        }
        return self::$tran[$name] = $obj;
    }
    
    public static function block(){
        foreach( self::$tran as $obj ){
            $obj->rollback();
        }
        foreach( self::$rollback_callbacks as $info ){
            self::triggerCallback( $info['cb'], $info['params'] );
        }
        self::$commit_callbacks = array();
        self::$rollback_callbacks = array();
    }
    
    public static function rollback(){
        self::block();
        self::reset();
        return true;
    }

    function inProgress() {
    	return (empty(self::$tran)) ? FALSE : TRUE;
    }

    public static function commit(){
        if (!self::inProgress()) return FALSE;
        $status = false;
        foreach( self::$tran  as $k => $obj )
        {
            $status = $obj->commit();
            if( ! $status ){
                self::rollback();
                return FALSE;
            }
        }    
        foreach( self::$commit_callbacks as $info ){
            self::triggerCallback( $info['cb'], $info['params'] );
        }
        self::reset();
        return $status;
    }
    public static function reset(){
        self::$at_start = TRUE;
        self::$commit_callbacks = array();
        self::$rollback_callbacks = array();
        self::$tran = array();
    }

    function atStart(){
        return self::$at_start;
    }

    function claimStart(){
        if( ! self::$at_start  ) return FALSE;
        self::$at_start = FALSE;
        return TRUE;
    }
    
    public static function onCommit( $cb, array $params = array() ){
        self::$commit_callbacks[ self::hashCallback( $cb, $params ) ] = array( 'cb'=> $cb, 'params'=>$params );
    }

    public static function onRollback( $cb, array $params = array() ){
        self::$rollback_callbacks[ self::hashCallback( $cb, $params ) ] = array( 'cb'=> $cb, 'params'=>$params );
    }

    protected static function triggerCallback( $cb, array $params ){
        if( ! is_callable( $cb ) ) return;
        return call_user_func_array( $cb, $params );
    }

    protected static function hashCallback( $cb, $params ){
        if( is_array( $cb ) && isset( $cb[0] ) && is_object( $cb[0] ) ) $cb[0] = spl_object_hash( $cb[0] );
        foreach( $params as $k => $v ){
            if( is_object( $v ) ) $params[ $k ] = spl_object_hash( $v );
        }
        return md5( serialize(array( $cb, $params ) ) );
    }
}
