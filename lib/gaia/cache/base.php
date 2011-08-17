<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Cache;
use Memcache;

class Base extends Memcache {
    const UNDEF = "\0__undef__\0";
    protected $queue = array();

    // fixing a problem introduced by the upgrade of the Pecl Memcache Extension from 2.2.4 -> 3.0.3
    // the newer pecl extension returns false on no results, whereas the older version returned an
    // empty array. we want the older behavior.
    public function get( $k, $options = NULL ){
        if( is_scalar( $k ) ) return parent::get( $k );
        if( ! is_array( $k ) ) return FALSE;
        if( count( $k ) < 1 ) return array();
        $res = parent::get( $k );
        if( is_array( $res ) ) return $res;
        return array();
    }
    
    public function add( $k, $v, $compress = 0, $ttl = NULL ){
        if( is_scalar( $v ) && strlen( $v ) < 100) $compress = 0;
        return parent::add($k, $v, $compress, $ttl );
    }
    
    public function set( $k, $v, $compress = 0, $ttl = NULL ){
        if( is_scalar( $v ) && strlen( $v ) < 100) $compress = 0;
        return parent::set($k, $v, $compress, $ttl );
    }
    
    public function replace( $k, $v, $compress = 0, $ttl = NULL ){
        if( is_scalar( $v ) && strlen( $v ) < 100) $compress = 0;
        return parent::replace($k, $v, $compress, $ttl );
    }
    
    public function increment( $k, $v = 1 ){
        return parent::increment($k, $v );
    }
    
    public function decrement( $k, $v = 1 ){
        return parent::decrement($k, $v );
    }
    
    public function delete( $k ){
        return parent::delete( $k, 0);
    }
    
    public function queue( $keys, $options = NULL ){
        if( ! is_array( $keys ) ) $keys = array( $keys );
        if( ! $options instanceof Options ) $options = new Options( $options );
        foreach( $keys as $k ){
            $key = $options->prefix . $k;
            $this->queue[ $key ] = $options;
        }
    }
    
    public function fetchAll(){
        $queue = $this->queue;
        $this->queue = array();
        $res = $this->get( array_keys( $queue ) );
        if( ! is_array( $res ) ) return array();
        foreach( array_keys( $res) as $k) unset( $queue[ $k ]->missing_callback );
        $missing = $callbacks = array();
        foreach( $queue as $k=>$options ){
            if( isset( $options->missing_callback ) ){
                $cb_key = md5( serialize( $options->missing_callback ) );
                if( ! isset( $callbacks[ $cb_key ] ) ) $callbacks[ $cb_key ] = $options->missing_callback;
                if( ! isset( $missing[ $cb_key ] ) ) $missing[ $cb_key ] = array();
                $missing[ $cb_key ][substr($k, strlen($options->prefix)) ] = $k;
            }
        }
        foreach( $missing as $cb_key => $keys ){
            $cb = $callbacks[ $cb_key ];
            $direct_res = call_user_func( $cb, array_keys($keys));
            if( ! is_array( $direct_res ) ) $direct_res = array();
            if( $options->cache_missing ){
                foreach($keys as $k ){
                    if( ! isset( $direct_res[ $k ] ) ) $direct_res[ $k ] = self::UNDEF; 
                }
            }
            foreach( $direct_res as $key=>$v ){
                $k = $keys[ $key ];
                $res[ $k ] = $v;
                $this->set( $k, $v, $queue[ $k ]->compression, $queue[ $k ]->timeout );
            }
            if( isset( $options->default ) ) {
                foreach( $keys as $k ){
                    if( ! isset( $res[ $k ] ) ) $res[$k] = $options->default;
                }
            }
            
        }
        
        foreach( $res as $k=>$v){
            if( $v === self::UNDEF ){
                unset( $res[ $k ] );
                continue;
            }
            if( $queue[ $k ]->response_callback ) call_user_func( $queue[ $k ]->response_callback, substr($k, strlen($queue[$k]->prefix)), $v );
        }
        return $res;
    }
}
