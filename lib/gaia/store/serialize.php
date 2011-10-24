<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;


// basic wrapper to make redis library conform to the cache interface.
// todo: figure out ways to make some of the more elegant list and member set functionality 
// of redis available through the wrapper interface without breaking things.
class Serialize extends Wrap {
    
    const SERIALIZE_PREFIX = '#__PHP__:';

    public function get( $request ){
        if( is_scalar( $request ) ) {
            return $this->unserialize( $this->core->get( $request ) );
        }
        if( ! is_array( $request ) ) return NULL;
        if( count( $request ) < 1 ) return array();
        $res = $this->core->get( $request );
        if( ! is_array( $res ) ) return array();
        foreach($res as $key => $value ){
            $res[ $key ] = $this->unserialize($value);
        }
        return  $res;
    }
    
    public function add( $k, $v, $ttl = NULL ){
        return $this->core->add( $k, $this->serialize( $v ), $ttl );
    }
    
    public function set( $k, $v, $ttl = NULL ){
        return $this->core->set($k, $this->serialize($v), $ttl);
    }
    
    public function replace( $k, $v, $ttl = NULL ){
        return $this->core->replace( $k, $this->serialize( $v ), $ttl );
    }
    
    protected function serialize($v){
        if( is_scalar( $v ) || is_numeric( $v ) ) return $v;
        return self::SERIALIZE_PREFIX . serialize( $v );
    }
    
    protected function unserialize( $v ){
        if( $v === NULL || $v === FALSE ) return NULL;
        if( ! is_scalar( $v ) ) return $v;
        $len = strlen(self::SERIALIZE_PREFIX);
        if( substr( $v, 0, $len) != self::SERIALIZE_PREFIX) return $v;
        return unserialize(substr( $v, $len) );
    }
}