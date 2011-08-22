<?php
namespace Gaia\Cache;

class Replica extends Wrap {

    private $replicas;
    const DEFAULT_TTL = 259200;
    function __construct( Iface $core, $replicas = NULL ){ 
        $this->replicas = ( $replicas && is_numeric( $replicas) && $replicas > 1) ? intval($replicas) : 3;
        parent::__construct( $core );
    }
    
    function get( $__key, $options = NULL ){
        $keys = is_scalar( $__key ) ? array($__key) : $__key;
        if( ! is_array($keys ) ) return FALSE;
        foreach( $keys as $k ) {
            $matches[ $k ] = NULL;
            $matches[ $exp_keys[] = '/exp/' . $k ] = NULL;
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
        foreach( $exp_keys as $k ){
            if( ! isset( $matches[ ($parent_key = substr( $k,5)) ] ) ) continue;
            $diff = ( ctype_digit( (string) $matches[ $k ] ) ) ? $matches[ $k ] - $now : 0;
            if( $diff > 0 && // if the soft ttl is too old, reset it
                mt_rand(1, pow( $diff, 3) ) != 1 // randomly reset it based on a parabolic curve approaching timeout.
            ) continue;
            if( ! $this->core->add( $k . '/REPLICA/r-lock', 1, 5) && 
                  $this->core->get( $k . '/REPLICA/r-lock') ) continue;
            if( $diff < 10 ){
                foreach( $replicas as $i ){
                    $this->core->set($k . '/REPLICA/' . $i, $now + 10);
                }
            }
            unset( $matches[ $parent_key ] );
        }
        $res = array();
        foreach( $keys as $k ){
            if( ! isset( $matches[ $k ] ) ) continue;
            $res[ $k ] = $matches[ $k ];
        }
        if( is_scalar( $__key ) ) return isset( $res[ $__key ] ) ? $res[ $__key ] : FALSE;
        
        return $res;
    }
    
    function set($k, $v, $expire = 0 ){
        $this->core->set($k . '/REPLICA/lock', 1, $expire );
        $res = FALSE;
        if( ! $expire ) $expire = self::DEFAULT_TTL;
        $replicas = range(1, $this->replicas );
        foreach( $replicas as $i){
            $r = $this->core->set($k . '/REPLICA/' . $i, $v);
            if( $r ) $res = $r;
            $this->core->set('/exp/' . $k . '/REPLICA/' . $i, time() + $expire);
        }
        return $res;
    }
    
    function delete( $k ){
        $res = TRUE;
        $replicas = range(1, $this->replicas );
        foreach( $replicas as $i){
            $this->core->delete('/exp/' . $k . '/REPLICA/' . $i );
            $r = $this->core->delete($k . '/REPLICA/' . $i);
            if( ! $r ) $res = FALSE;
        }
        return $res;
    }   
    
    function add($k, $v, $expire = 0 ){
        if( ! $this->core->add($k . '/REPLICA/lock', 1, $expire ) ) return FALSE;
        $res = FALSE;
        if( ! $expire ) $expire = self::DEFAULT_TTL;
        $replicas = range(1, $this->replicas );
        foreach( $replicas as $i){
            $r = $this->core->set($k . '/REPLICA/' . $i, $v );
            if( $r ) $res = $r;
            $this->core->set('/exp/' . $k . '/REPLICA/' . $i, time() + $expire);
        }
        return $res;
    }
    
    function replace($k, $v, $expire = 0 ){
        if( ! $this->core->replace($k . '/REPLICA/lock', 1, $expire ) ) return FALSE;
        $res = FALSE;
        if( ! $expire ) $expire = self::DEFAULT_TTL;
        $replicas = range(1, $this->replicas );
        foreach( $replicas as $i){
            $r = $this->core->set($k . '/REPLICA/' . $i, $v );
            if( $r ) $res = $r;
            $this->core->set('/exp/' . $k . '/REPLICA/' . $i, time() + $expire);
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
            $this->core->set('/exp/' . $k . '/REPLICA/' . $i, time() + self::DEFAULT_TTL );
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
            $this->core->set('/exp/' . $k . '/REPLICA/' . $i, time() + self::DEFAULT_TTL);
        }
        return $res;
    }
}
