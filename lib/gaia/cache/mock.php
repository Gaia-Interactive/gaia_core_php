<?php
namespace Gaia\Cache;

/**
* make APC conform to our cache interface. Works pretty well except for the replace call, since apc
* doesn't exactly support that. I can fake it though.
*/
class Mock Implements Iface {

    protected static $data = array();
    public static $time_offset = 0;
    
    public function flush(){
        self::$data = array();
    }
    
    function set( $k, $v, $expires = 0 ){
        if( $v === FALSE ) return FALSE;
        if( $expires < 1 ){
            $expires = 0;
        } else {
            $expires = self::time() + $expires;
        }
        self::$data[ $k ] = array( $v, $expires );
        return TRUE;
    }
    
    function add( $k, $v, $expires = 0 ){
        $res = $this->get( $k );
        if( $res !== FALSE ) return FALSE;
        $res = $this->set( $k, $v, $expires );
        return $res;
    }
    
    function replace( $k, $v, $expires = 0 ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v, $expires );
    }
    
    function increment( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return self::$data[ $k ][0] = bcadd( $v, $step );
    }
    
    function decrement( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return self::$data[ $k ][0] = bcsub( $v, $step );
    }
    
    function delete( $k ){
        unset( self::$data[ $k ] );
        return TRUE;
    }
    
    public function get( $key ){
        if( is_array( $key ) ) return $this->getMulti( $key );
        if( ! isset( self::$data[ $key ] ) ) return FALSE;
        $expires = self::$data[ $key ][1];
        if( $expires == 0 ) return self::$data[ $key ][0];
        if( $expires < self::time() ) {
            unset( self::$data[ $key ] );
            return FALSE;
        }
        return self::$data[ $key ][0];
    }
    
    protected function getMulti( array $keys ){
        $res = array();
        foreach( $keys as $key ){
            $v = $this->get( $key );
            if( $v === FALSE ) continue;
            $res[ $key ] = $v;
        }
        return $res;
    }
    
    protected function time(){
        return time() + self::$time_offset;
    }
}