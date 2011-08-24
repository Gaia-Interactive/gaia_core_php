<?php
namespace Gaia\Cache;

/**
* The wrap class implements the caching interface and allows us to pass calls inward to a core
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
    
    public function get( $input, $options = NULL ){
        return $this->core->get( $input, $options );
    }
    
    public function __call($method, array $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
    }
}