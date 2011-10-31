<?php
namespace Gaia\Store;

class Replica implements Iface {

    private $replicas = array();
    const DEFAULT_TTL = 259200;
    
    public function __construct( array $replicas ){ 
        foreach( $replicas as $cache ){
            if( ! $cache instanceof Iface ){
                throw new Exception('invalid cache');
            }
            $this->replicas[] = $cache;
        }
        if( count( $this->replicas ) < 1 ) throw new Exception('invalid cache replicas');
    }
    
    public function get( $request ){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return NULL;
        $res = $this->getMulti( array( $request ) );
        if( ! isset( $res[ $request ] ) ) return NULL;
        return $res[ $request ];
    }
    
    protected function getMulti( array $keys ){
        foreach( $keys as $k ) {
            $matches[ $k ] = NULL;
        }
        $replicas = $this->replicas;
        shuffle( $replicas );
        foreach( $replicas as $cache ){
            $ask = array_keys( $matches, NULL, TRUE);
            if( count( $ask ) < 1 ) break;
            $res = $cache->get( $ask );
            if( ! is_array( $res ) ) $res = array();
            foreach( $res as $k=>$v ){
                $matches[ $k ] = $v;
            }
        }
        $res = array();
        foreach( $keys as $k ){
            if( ! isset( $matches[ $k ] ) ) continue;
            $res[ $k ] = $matches[ $k ];
        }        
        return $res;
    }
    
    function set($k, $v, $expire = 0 ){
        $res = FALSE;
        $replicas = $this->replicas;
        shuffle( $replicas );
        foreach( $replicas as $cache){
            $r = $cache->set($k, $v, $expire);
            if( $r ) $res = $r;
        }
        return $res;
    }
    
    function delete( $k ){
        $res = TRUE;
        $replicas = $this->replicas;
        shuffle( $replicas );
        foreach( $replicas as $cache){
            $r = $cache->delete($k);
            if( ! $r ) $res = FALSE;
        }
        return $res;
    }   
    
    function add($k, $v, $expire = 0 ){
        $res = FALSE;
        $replicas = $this->replicas;
        shuffle( $replicas );
        $method = __FUNCTION__;
        $repair = array();
        foreach( $replicas as $cache){
            $r = $cache->{$method}($k, $v, $expire );
            if( $r ){
                $res = $r;
                $method = 'set';
            } else {
                $repair[] = $cache;
            }
        }
        if( $res ){
            foreach( $repair as $cache ) $cache->set( $k, $v, $expire );
        }
        return $res;
    }
    
    function replace($k, $v, $expire = 0 ){
        $res = FALSE;
        $replicas = $this->replicas;
        shuffle( $replicas );
        $method = __FUNCTION__;
        $repair = array();
        foreach( $replicas as $cache){
            $r = $cache->$method($k, $v, $expire );
            if( $r ){
                $res = $r;
                $method = 'set';
            } else {
                $repair[] = $cache;
            }
        }
        if( $res ){
            foreach( $repair as $cache ) $cache->set( $k, $v, $expire );
        }
        return $res;
    }
    
    function increment($k, $v = 1){
        $res = FALSE;
        $replicas = $this->replicas;
        shuffle( $replicas );
        $method = 'increment';
        $repair = array();
        foreach( $replicas as $cache){
            $r = $cache->$method($k, $v );
            if( $r ) {
                $res = $r;
                $method = 'set';
                $v = $res;
            } else {
                $repair[] = $cache;
            }
        }
        if( $res ){
            foreach( $repair as $cache ) $cache->set( $k, $v );
        }
        return $res;
    }
    
    function decrement($k, $v = 1){
                $res = FALSE;
        $replicas = $this->replicas;
        shuffle( $replicas );
        $method = 'decrement';
        $repair = array();
        foreach( $replicas as $cache){
            $r = $cache->$method($k, $v );
            if( $r ) {
                $res = $r;
                $method = 'set';
                $v = $res;
            } else {
                $repair[] = $cache;
            }
        }
        if( $res ){
            foreach( $repair as $cache ) $cache->set( $k, $v );
        }
        return $res;
    }
    
    public function flush(){
        return FALSE;
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
        
    public function ttlEnabled(){
        return TRUE;
    }
}
