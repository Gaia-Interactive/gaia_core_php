<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;

// basic wrapper to make redis library conform to the cache interface.
// todo: figure out ways to make some of the more elegant list and member set functionality 
// of redis available through the wrapper interface without breaking things.
class Redis extends Serialize {
    
    public function __construct( $redis = NULL ){
        $this->core = $redis instanceof \Predis\Client ? $redis : new \Predis\Client( $redis );
    }

    public function get( $request ){
        if( is_scalar( $request ) ) {
            return $this->unserialize( $this->core->get( $request ) );
        }
        if( ! is_array( $request ) ) return FALSE;
        if( count( $request ) < 1 ) return array();
        $res = $this->core->mget( $request );
        if( ! is_array( $res ) ) return array();
        $list = array();
        foreach( array_values($request ) as $i => $key ){
            if( ! isset( $res[ $i ] ) || $res[ $i ] === NULL ) continue;
            $list[ $key ] = $this->unserialize($res[ $i ]);
        }
        return  $list;
    }
    
    public function add( $k, $v, $ttl = NULL ){
        if( $this->get( $k ) ) return FALSE; // unexpected behavior in redis where if ttl is set, it will allow an add to work even if the key is set.
        $res = $this->core->setnx($k, $this->serialize($v) );
        if( ! $res ) return $res;
        if( $ttl === NULL ) return TRUE;
        return $this->core->expire( $k, $ttl );
    }
    
    public function set( $k, $v, $ttl = NULL ){
        if( $ttl !== NULL ) return $this->core->setex($k, $ttl, $this->serialize($v));
        return $this->core->set($k, $this->serialize($v));
    }
    
    public function replace( $k, $v, $ttl = NULL ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set($k, $v, $ttl );
    }
    
    public function increment( $k, $v = 1 ){
        return $this->core->incrby($k, $v );
    }
    
    public function decrement( $k, $v = 1 ){
        return $this->core->decrby($k, $v );
    }
    
    public function delete( $k ){
        return $this->core->del( $k );
    }
}