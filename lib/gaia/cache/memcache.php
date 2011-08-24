<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Cache;

/*
* conform the pecl memcache client to our own interface. Most notable difference is the compression
* flag is auto-populated here based on the data passed to the client rather than letting the app
* determine what to do. Easier and better for us to determine based on the data value whether or not
* to compress it. Devs usually mess that up when left to their own devices, and it clutters the
* interface.
*/
class Memcache extends \Memcache implements Iface {

    const COMPRESS_THRESHOLD = 1000;

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
    
    public function add( $k, $v, $ttl = NULL ){
        return parent::add($k, $v, self::should_compress( $v ), $ttl );
    }
    
    public function set( $k, $v, $ttl = NULL ){
        return parent::set($k, $v, self::should_compress( $v ), $ttl );
    }
    
    public function replace( $k, $v, $ttl = NULL ){
        return parent::replace($k, $v, self::should_compress( $v ), $ttl );
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
    
    protected static function should_compress( $v ){
        if( is_array( $v ) && count($v) >= self::COMPRESS_THRESHOLD ) return MEMCACHE_COMPRESSED;
        if( is_object( $v ) ) return MEMCACHE_COMPRESSED;
        $len = is_scalar( $v ) ? strlen( strval($v) ) : strlen( print_r($v, TRUE) );
        return $len < self::COMPRESS_THRESHOLD ? 0 : MEMCACHE_COMPRESSED;
    }
}
