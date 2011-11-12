<?php
namespace Gaia\Store;
use Gaia\Time;

class EmbeddedTTL extends Wrap {

    public function set( $k, $v, $ttl = 0 ){
        if( $v === FALSE ) return FALSE;
        if( $v === NULL ) return $this->delete( $k );
        if( $ttl < 1 ){
            $ttl = 0;
        } else {
            $ttl = Time::now() + $ttl;
        }
        $this->core->set( $k, array( $v, $ttl ));
        return TRUE;
    }
    
    public function increment( $k, $step = 1){
        if( ! is_scalar( $k ) ) return FALSE;
        $res = $this->core->get( $k );
        if( ! $this->_isValid( $k, $res ) ) return FALSE;
        $res[0] = strval( $res[0] );
        if( ! ctype_digit( $res[0] ) ) return FALSE;
        $res[0] = bcadd( $res[0], $step );
        $this->core->set( $k, $res );
        return $res[0];
    }
    
    public function decrement( $k, $step = 1){
        if( ! is_scalar( $k ) ) return FALSE;
        $res = $this->core->get( $k );
        if( ! $this->_isValid( $k, $res ) ) return FALSE;
        $res[0] = strval( $res[0] );
        if( ! ctype_digit( $res[0] ) ) return FALSE;
        $res[0] = bcsub( $res[0], $step );
        $this->core->set( $k, $res );
        return $res[0];
    }
    
    public function get( $key ){
        if( is_array( $key ) ) {
            $list = array();
            foreach( $this->core->get( $key ) as $_k => $res ){
                if( ! $this->_isValid( $_k, $res ) ) continue;
                $list[ $_k ] = $res[0];
            }
            return $list;
        }
        $res = $this->core->get( $key );
        if( ! $this->_isValid( $key, $res ) ) return NULL;
        return $res[0];
    }
    
        
    public function add( $name, $value, $ttl = 0 ){
        if( $this->__isset( $name ) ) return FALSE;
        return $this->set( $name, $value, $ttl );
    }
    
    public function replace( $name, $value, $ttl = 0 ){
        if( ! $this->__isset( $name ) ) return FALSE;
        return $this->set( $name, $value, $ttl );
    }
    
    public function ttlEnabled(){
        return TRUE;
    }
    
    protected function _isValid( $k, $v ){
        if( $v === NULL ) return FALSE;
        if( ! is_array( $v ) ){
            $this->core->delete( $k );
            return FALSE;
        }
        $ttl = $v[1];
        if( $ttl == 0 ) return TRUE;
        if( $ttl < Time::now() ){
            $this->core->delete( $k );
            return FALSE;
        }
        return TRUE;
    }

    public function __isset( $k ){
        return ( $this->get( $k ) !== NULL ) ? TRUE  : FALSE;
    }
    
}