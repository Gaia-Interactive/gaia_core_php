<?php
namespace Gaia\Store;

/**
* The wrap class implements the interface and allows us to pass calls inward to a core
* object with the same interface. This allows us to intercept the calls on their way inward if 
* we want and change the behavior.
*
* We can append a namespace to the key, or do key replicas, or any number of other tricks. 
*/
class Wrap implements Iface {
    protected $core;
    const UNDEF = "\0__undef__\0";

    public function __construct( Iface $core ){
        $this->core = $core;
    }
    
    public function add( $key, $value, $expires = 0){
        return $this->core->add( $key, $value, $expires );
    }
    
    public function set( $key, $value, $expires = 0){
        return $this->core->set( $key, $value, $expires );
    }
    
    public function replace( $key, $value, $expires = 0){
        return $this->core->replace( $key, $value, $expires );
    }
    
    public function increment( $key, $value = 1 ){
        return $this->core->increment( $key, $value );
    }
    
    public function decrement( $key, $value = 1 ){
        return $this->core->decrement( $key, $value );
    }
    
    public function get( $input ){
        return $this->core->get( $input );
    }
    
    public function delete( $key ){
        return $this->core->delete( $key );
    }
    
    public function flush(){
        return $this->core->flush();
    }
    
    public function supportsTTL(){
        return $this->core->supportsTTL();
    }

    
    public function __call($method, array $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
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