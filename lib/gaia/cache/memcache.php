<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Cache;
use Gaia\StorageIface as Iface;

/*
* conform the pecl memcache client to our own interface. Most notable difference is the compression
* flag is auto-populated here based on the data passed to the client rather than letting the app
* determine what to do. Easier and better for us to determine based on the data value whether or not
* to compress it. Devs usually mess that up when left to their own devices, and it clutters the
* interface.
*/
class Memcache extends Wrap implements Iface {

    const COMPRESS_THRESHOLD = 2000;
    
    protected $conns = array();
    
    public function __construct(){
        if( class_exists( '\Memcached' )){
            $this->core = new \Memcached;
        } elseif( class_exists('\Memcache') ) {
            $this->core = new \Memcache;
        } else {
            trigger_error('no memcache class found', E_USER_ERROR);
            exit();
        }
    }
    
    // fixing a problem introduced by the upgrade of the Pecl Memcache Extension from 2.2.4 -> 3.0.3
    // the newer pecl extension returns false on no results, whereas the older version returned an
    // empty array. we want the older behavior.
    public function get( $k ){
        if( is_scalar( $k ) ) return $this->core->get( $k );
        if( ! is_array( $k ) ) return FALSE;
        if( count( $k ) < 1 ) return array();
        $res = ( $this->core instanceof \Memcached ) ? $this->core->getMulti( $k ) : $this->core->get( $k );
        if( is_array( $res ) ) return $res;
        return array();
    }
    
    public function add( $k, $v, $ttl = NULL ){
        if( $this->core instanceof \Memcached ) return $this->core->add( $k, $v, $ttl );
        return $this->core->add($k, $v, self::should_compress( $v ), $ttl );
    }
    
    public function set( $k, $v, $ttl = NULL ){
        if( $this->core instanceof \Memcached ) return $this->core->set( $k, $v, $ttl );
        return $this->core->set($k, $v, self::should_compress( $v ), $ttl );
    }
    
    public function replace( $k, $v, $ttl = NULL ){
        if( $this->core instanceof \Memcached ) return $this->core->replace( $k, $v, $ttl );
        return $this->core->replace($k, $v, self::should_compress( $v ), $ttl );
    }
    
    public function increment( $k, $v = 1 ){
        return $this->core->increment($k, $v );
    }
    
    public function decrement( $k, $v = 1 ){
        return $this->core->decrement($k, $v );
    }
    
    public function delete( $k ){
        return $this->core->delete( $k, 0);
    }
    
    public function replicas( $ct = NULL ){
        $conns = $this->servers();
        $max = count( $conns );
        if( $ct < 1 || $ct > $max) $ct = $max;
        $replicas = array();
        foreach( $conns as $i=>$conn ){
            $hash = $i % $ct;
            if( ! isset( $replicas[ $hash ] ) ) $replicas[ $hash ] = new self;
            $replicas[ $hash ]->addServer( $conn['host'], $conn['port'], $conn['weight'] );
        }
        return $replicas;
    }
    
    public function servers(){
        return $this->conns;
    }
    
    public function addServer( $host, $port, $weight = 0 ){
        $this->conns[] = array( 'host'=>$host, 'port'=>$port, 'weight'=>$weight );
        return $this->core->addServer( $host, $port, $weight );
    }
    
    protected static function should_compress( $v ){
        if( is_array( $v ) && count($v) >= self::COMPRESS_THRESHOLD ) return MEMCACHE_COMPRESSED;
        if( is_object( $v ) ) return MEMCACHE_COMPRESSED;
        $len = is_scalar( $v ) ? strlen( strval($v) ) : strlen( print_r($v, TRUE) );
        return $len < self::COMPRESS_THRESHOLD ? 0 : MEMCACHE_COMPRESSED;
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
