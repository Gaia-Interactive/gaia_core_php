<?php
namespace Gaia\Store;

/**
* The wrap class implements the interface and allows us to pass calls inward to a core
* object with the same interface. This allows us to intercept the calls on their way inward if 
* we want and change the behavior.
*
* We can append a namespace to the key, or do key replicas, or any number of other tricks. 
*/
class Closure implements Iface {

    protected $closures = array();

    public function __construct( array $closures ){
        foreach( $closures as $method => $closure ){
            $this->attach( $method, $closure );
        }
    }
    
    public function attach( $method, $closure ){
        if( ! $closure instanceof \Closure ) {
            trigger_error('non-closure attached to ' . get_class( $this ), E_USER_ERROR);
            exit(1);
        }
        return $this->closures[ strtolower($method) ] = $closure;
    }
    
    public function add( $key, $value, $ttl = 0){
        if( isset( $this->closures[ __FUNCTION__ ] ) ){
            $f = $this->closures[ __FUNCTION__ ];
            return (bool) $f( $key, $value, $ttl );
        }
        if( $this->get( $key ) ) return FALSE;
        return $this->set( $key, $value, $ttl );
    }
    
    public function set( $key, $value, $ttl = 0){
        if( ! isset( $this->closures[ __FUNCTION__ ] ) ) return FALSE;
        $f = $this->closures[ __FUNCTION__ ];
        return (bool) $f( $key, $value, $ttl );
    }
    
    public function replace( $key, $value, $ttl = 0){
        if( isset( $this->closures[ __FUNCTION__ ] ) ){
            $f = $this->closures[ __FUNCTION__ ];
            return (bool) $f( $key, $value, $ttl );
        }
        if( ! $this->get( $key ) ) return FALSE;
        return $this->set( $key, $value, $ttl );
    }
    
    public function increment( $key, $value = 1 ){
        if( isset( $this->closures[ __FUNCTION__ ] ) ){
            $f = $this->closures[ __FUNCTION__ ];
            $ret = $f( $key, $value );
            if( ! $ret || ! ctype_digit( strval( $ret ) ) ) return FALSE;
            return $ret;
        }
        $ret = $this->get( $key );
        if( ! $ret || ! ctype_digit( strval( $ret ) ) ) return FALSE;
        if( ! $this->set( $key, $ret = bcadd( $ret, $value ) ) ) return FALSE;
        return $ret;
    }
    
    public function decrement( $key, $value = 1 ){
        if( isset( $this->closures[ __FUNCTION__ ] ) ){
            $f = $this->closures[ __FUNCTION__ ];
            $ret = $f( $key, $value );
            if( ! $ret || ! ctype_digit( strval( $ret ) ) ) return FALSE;
            return $ret;
        }
        $ret = $this->get( $key );
        if( ! $ret || ! ctype_digit( strval( $ret ) ) ) return FALSE;
        if( ! $this->set( $key, $ret = bcsub( $ret, $value ) ) ) return FALSE;
        return $ret;
    }
    
    public function get( $input ){
        if( ! isset( $this->closures[ __FUNCTION__ ] ) ){
            return is_array( $input ) ? array() : NULL;
        }
        $f = $this->closures[ __FUNCTION__ ];
        $ret = $f( $input );
        if( is_array( $input ) && ! is_array( $ret ) ) return array();
        if( $ret === FALSE ) return NULL;
        return $ret;
    }
    
    public function delete( $key ){
        if( isset( $this->closures[ __FUNCTION__ ] ) ){
            $f = $this->closures[ __FUNCTION__ ];
            return (bool) $f( $key );
        }
        if( ! $this->get( $key ) ) return TRUE;
        return $this->set( $key, NULL );
    }
    
    public function flush(){
        if( ! isset( $this->closures[ __FUNCTION__ ] ) ) return;
        $f = $this->closures[ __FUNCTION__ ];
        $f();
    }
    
    public function ttlenabled(){
        if( ! isset( $this->closures[ __FUNCTION__ ] ) ) return FALSE;
        $f = $this->closures[ __FUNCTION__ ];
        return $f();
    }
    
    public function __call($method, array $args ){
        if( ! isset( $this->closures[ __FUNCTION__ ] ) ) {
            trigger_error('undefined method ' . get_class($this) . '::' . __FUNCTION__, E_USER_ERROR);
            exit(1);
        }
        $f = $this->closures[ __FUNCTION__ ];
        return $f( $method, $args );
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
    }
    
    public function __set( $k, $v ){
        if( ! $this->set( $k, $v ) ) return FALSE;
        return $v;
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