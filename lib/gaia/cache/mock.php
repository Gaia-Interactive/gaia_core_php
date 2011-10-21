<?php
namespace Gaia\Cache;
use Gaia\StorageIface as Iface;

class Mock Implements Iface {

    protected static $data = array();
    public static $time_offset = 0;
    
    public function flush(){
        self::$data = array();
    }
    
    public function set( $k, $v, $expires = 0 ){
        if( $v === FALSE ) return FALSE;
        if( $expires < 1 ){
            $expires = 0;
        } else {
            $expires = self::time() + $expires;
        }
        self::$data[ $k ] = array( $v, $expires );
        return TRUE;
    }
    
    public function add( $k, $v, $expires = 0 ){
        $res = $this->get( $k );
        if( $res !== FALSE ) return FALSE;
        $res = $this->set( $k, $v, $expires );
        return $res;
    }
    
    public function replace( $k, $v, $expires = 0 ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v, $expires );
    }
    
    public function increment( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return self::$data[ $k ][0] = bcadd( $v, $step );
    }
    
    public function decrement( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return self::$data[ $k ][0] = bcsub( $v, $step );
    }
    
    public function delete( $k ){
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
    
    public function offset( $increment = 0 ){
        return self::$time_offset += $increment;
    }
    
    protected function time(){
        return time() + self::$time_offset;
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
    }
    public function __set( $k, $v ){
        return $this->set( $k, $v );
    }
    public function __get( $k ){
        return $this->get( $k );
    }
    public function __unset( $k ){
        return $this->delete( $k );
    }
    public function __isset( $k ){
        $v = $this->get( $k );
        if( $v === FALSE || $v === NULL ) return FALSE;
        return TRUE;
    } 
    
}