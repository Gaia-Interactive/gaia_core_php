<?php
namespace Gaia\Cache;

class Replica extends Wrap {

    private $replicas;
    const DEFAULT_TTL = 259200;
    function __construct( Iface $core, $replicas = NULL ){ 
        $this->replicas = ( $replicas && is_numeric( $replicas) && $replicas > 1) ? intval($replicas) : 3;
        parent::__construct( $core );
    }
    
    public function get( $request, $options = NULL ){
        if( is_array( $request ) ) return $this->getMulti( $request, $options );
        if( ! is_scalar( $request ) ) return FALSE;
        $res = $this->getMulti( array( $request ), $options );
        if( ! isset( $res[ $request ] ) ) return FALSE;
        return $res[ $request ];
    }
    
    protected function getMulti( array $keys, $options = NULL ){
        foreach( $keys as $k ) {
            $matches[ $k ] = NULL;
        }
        
        $replicas = range(1, $this->replicas);
        shuffle( $replicas );
        foreach( $replicas as $i ){
            $ask = array();
            foreach( $matches as $k=>$v){
                if( $v !== NULL ) continue;
                $ask[ $k . '/REPLICA/' . $i ] = $k;
            }
            if( count( $ask ) < 1 ) break;
            $res = $this->core->get( array_keys( $ask ) );
            
            if( ! is_array( $res ) ) $res = array();
            foreach( $res as $k=>$v ){
                $matches[ $ask[ $k ] ] = $v;
            }
        }
        $now = time();
        $res = array();
        foreach( $keys as $k ){
            if( ! isset( $matches[ $k ] ) ) continue;
            $res[ $k ] = $matches[ $k ];
        }        
        return $res;
    }
    
    function set($k, $v, $expire = 0 ){
        $res = FALSE;
        $replicas = range(1, $this->replicas );
        foreach( $replicas as $i){
            $r = $this->core->set($k . '/REPLICA/' . $i, $v, $expire);
            if( $r ) $res = $r;
        }
        return $res;
    }
    
    function delete( $k ){
        $res = TRUE;
        $replicas = range(1, $this->replicas );
        foreach( $replicas as $i){
            $r = $this->core->delete($k . '/REPLICA/' . $i);
            if( ! $r ) $res = FALSE;
        }
        return $res;
    }   
    
    function add($k, $v, $expire = 0 ){
        $res = FALSE;
        $replicas = range(1, $this->replicas );
        $method = __FUNCTION__;
        foreach( $replicas as $i){
            $r = $this->core->{$method}($k . '/REPLICA/' . $i, $v, $expire );
            if( $r ){
                $res = $r;
                $method = 'set';
            }
        }
        return $res;
    }
    
    function replace($k, $v, $expire = 0 ){
        $res = FALSE;
        $replicas = range(1, $this->replicas );
        $method = __FUNCTION__;
        foreach( $replicas as $i){
            $r = $this->core->set($k . '/REPLICA/' . $i, $v );
            if( $r ){
                $res = $r;
                $method = 'set';
            }
        }
        return $res;
    }
    
    function increment($k, $v = 1){
        $res = FALSE;
        $replicas = range(1, $this->replicas );
        $method = 'increment';
        foreach( $replicas as $i){
            $r = $this->core->$method($k . '/REPLICA/' . $i, $v );
            if( $r ) {
                $res = $r;
                $method = 'set';
                $v = $res;
            }
        }
        return $res;
    }
    
    function decrement($k, $v = 1){
        $res = FALSE;
        $replicas = range(1, $this->replicas );
        $method = 'decrement';
        foreach( $replicas as $i){
            $r = $this->core->$method($k . '/REPLICA/' . $i, $v );
            if( $r ) {
                $res = $r;
                $method = 'set';
                $v = $res;
            }
        }
        return $res;
    }
}
