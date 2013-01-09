<?php

namespace Gaia\Test;

use Exception;
/**
* Sometimes it is difficult to test all possible behaviors of a class through straightforward
* blackbox testing. When external dependencies cannot easily be passed in consider making a stub 
* factory methods for them and injecting a stub so you can control the behavior.
* 
* When I do have to test with a stub class, I often do both. I test as much as possible without using
* stubs at all to make sure those external depencies work. then I inject stubs in to test things like
* error cases that aren't easily simulated, like database error handling, or conditions that trigger
* exceptions.
*/

class Stub {

    protected $m;
    
    public function __construct( array $methods ){
        $this->m = array();
        foreach( $methods as $k => $v ){
            if( ! $v instanceof \Closure ) throw new Exception("invalid method handler for: $k");
            $this->m[ strtolower( $k ) ] = $v;
        }
    }
    
    public function __call( $method, array $args ){
        $method = strtolower( $method );
        if( ! isset( $this->m[ $method ] ) ) throw new Exception('call to undefined method: ' . $method);
        $cb = $this->m[ $method ];
        return call_user_func_array( $cb, $args );
    }
    
    
    public function __isset($k ){
        if( isset( $this->m['__isset'] )) return $this->__call('__isset', array( $k ) );
        $v = $this->__get( $k );
        return isset( $v );
    }
    
    public function __get( $k ){
        return $this->__call('__get', array( $k ) );
    }
    
    public function __set( $k, $v){
        return $this->__call('__set', array( $k, $v ) );
    }
    
    public function __unset( $k ){
        if( isset( $this->m['__unset'] )) {
            $this->__call('__unset', array( $k ) );
            return;
        }
        $this->__call('__set', array( $k, NULL ) );
    }
}