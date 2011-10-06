<?php
namespace Gaia\ShortCircuit;
use Gaia\Cache;

/**
* allows us to cache the results of the resolver so we don't have to do expensive file I/O every
* time we need to resolve a URI to an action, or a view name to a template file.
*/
class CachedResolver {

    /**
    * used when writing to the cache to store a missing value.
    */
    const UNDEF = '__UNDEF__';
    
   /**
    * cache object
    */
    protected $cache;
    
    /**
    * the resolver object we are wrapping. should i convert the resolver to an interface?
    */
    protected $resolver;
    
    /**
    * class constructor.
    */
    public function __construct( Resolver $resolver , Cache\IFace $cache){
        $this->cache = $cache;
        $this->resolver = $resolver;
    }

    /**
    * wrap the search method in a cache.
    */
    public function search( $name, $type ){
        $dir =  $this->resolver->appdir();
        $key = md5( __CLASS__ . '/' . __FUNCTION__ . '/' . $dir . '/' . $type . '/' . $name);
        $n = $this->cache->get( $key );
        if( $n == self::UNDEF ) return '';
        if( $n ) return $n;
        $n = $this->resolver->search( $name, $type );
        if( $n ){
            $this->cache->set($key, $n, 300);
        } else {
            $this->cache->set($key, self::UNDEF, 30);
        }
        return $n;
    }
    
    /**
    * wrap the get method in a cache.
    */
    public function get($name, $type ) {
        $dir =  $this->resolver->appdir();
        $key = md5( __CLASS__ . '/' . __FUNCTION__ . '/' . $dir . '/' . $type . '/' . $name);
        $path = $this->cache->get( $key );
        if( $path == self::UNDEF ) return '';
        if( $path ) return $path;
        $path = $this->resolver->get( $name, $type );
        $this->cache->set( $key, ($path ? $path : self::UNDEF), (! $path ? 300 : 60) );
        return $path;
    }
    
    public function __call( $method, array $args ){
        return call_user_func_array( array( $this->resolver, $method ), $args );
    }
}

// EOF