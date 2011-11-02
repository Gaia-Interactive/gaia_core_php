<?php

namespace Gaia\DB;

class Transaction
{
    protected static $tran = array();
    protected static $depth = 0;
    protected static $commit_callbacks = array();
    protected static $rollback_callbacks = array();
    const SIGNATURE = '__TXN__';
    
    public static function add( Iface $obj ){
        $hash = spl_object_hash($obj);
        if( isset( self::$tran[ $hash ] ) ) return $obj;
        self::claimStart();
        if( ! $obj->start(self::SIGNATURE) ) {
            return FALSE;
        }
        return self::$tran[spl_object_hash($obj)] = $obj;
    }
    
    public static function connections(){
        return self::$tran;
    }
    
    public static function internals(){
        return array(
            'connections'=>self::$tran,
            'depth'=>self::$depth,
            'commit_callbacks'=>self::$commit_callbacks,
            'rollback_callbacks'=>self::$rollback_callbacks,
        );
    }
    
    public static function instance( $name ){
        $obj = Connection::instance( $name );
        self::add( $obj );
        return $obj;
    }
    
    public static function block(){
        $status = TRUE;
        if( ! self::$tran  ) return FALSE;
        foreach( self::$tran as $obj ){
            if( ! $obj->rollback(self::SIGNATURE) ) $status = FALSE;
        }
        foreach( self::$rollback_callbacks as $info ){
            self::triggerCallback( $info['cb'], $info['params'] );
        }
        self::$commit_callbacks = array();
        self::$rollback_callbacks = array();
        return $status;
    }
    
    public static function rollback(){
        $status = self::block();
        self::reset();
        return $status;
    }

    public static function started() {
    	return ( self::$depth != 0 &&  ! empty(self::$tran) ) ? TRUE : FALSE;
    }
    
    public static function inProgress() {
    	return self::started();
    }

    public static function commit(){
        if (!self::inProgress()) return FALSE;
        if( self::$depth < 1 ) return FALSE;
        if( self::$depth > 1 ){
            self::$depth--;
            return TRUE;
        }
        $status = false;
        foreach( self::$tran  as $k => $obj )
        {
            $status = $obj->commit(self::SIGNATURE);
            if( ! $status ){
                self::rollback();
                return FALSE;
            }
        }    
        foreach( self::$commit_callbacks as $info ){
            self::triggerCallback( $info['cb'], $info['params'] );
        }
        self::$tran = array();
        self::reset();
        return $status;
    }
    public static function reset(){
        self::$depth = 0;
        self::$commit_callbacks = array();
        self::$rollback_callbacks = array();
        foreach( self::$tran as $t ) {
            Connection::remove( $t );
        }
        self::$tran = array();
    }
    
    public static function start(){
        self::$depth++;
        return self::$depth;
    }

    public static function atStart(){
        return self::$depth == 0;
    }

    public static function claimStart(){
        if( self::$depth > 0 ) return FALSE;
        self::$depth++;
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
