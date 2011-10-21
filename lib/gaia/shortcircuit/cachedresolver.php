<?php
namespace Gaia\ShortCircuit;
use Gaia\StorageIface;

/**
* allows us to cache the results of the resolver so we don't have to do expensive file I/O every
* time we need to resolve a URI to an action, or a view name to a template file.
*/
class CachedResolver implements Iface\Resolver
{
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
    protected $core;
    
    /**
    * class constructor.
    */
    public function __construct( Iface\Resolver $resolver , StorageIface $cache){
        $this->cache = $cache;
        $this->core = $resolver;
    }

    /**
    * wrap the search method in a cache.
    */
    public function match( $uri,  & $args ){
        if( ! is_array( $args ) ) $args = array();
        $dir =  $this->core->appdir();
        $key = md5( __CLASS__ . '/' . __FUNCTION__ . '/' . $dir . '/' . $uri);
        $res = $this->cache->get( $key );
        if( $res == self::UNDEF ) return '';
        if( is_array( $res ) ) {
            if( is_array( $res['args'] ) ) $args = $res['args'];
            return $res['name'];
        }
        $name = $this->core->match( $uri, $args );
        if( $name ){
            $this->cache->set($key, array('name'=>$name, 'args'=>$args), 300);
        } else {
            $this->cache->set($key, self::UNDEF, 30);
        }
        return $name;
    }
    
    public function link( $action, array $params = array() ){
        return $this->core->link( $action, $params );
    }
    
    /**
    * wrap the get method in a cache.
    */
    public function get($name, $type ) {
        $dir =  $this->core->appdir();
        $key = md5( __CLASS__ . '/' . __FUNCTION__ . '/' . $dir . '/' . $type . '/' . $name);
        $path = $this->cache->get( $key );
        if( $path == self::UNDEF ) return '';
        if( $path ) return $path;
        $path = $this->core->get( $name, $type );
        $this->cache->set( $key, ($path ? $path : self::UNDEF), (! $path ? 300 : 60) );
        return $path;
    }
    
    public function appdir(){
        return $this->core->appdir();
    }
    
    public function setAppDir( $dir ){
        return $this->core->setAppDir( $dir );
    }
    
    public function __call( $method, $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
    }
}

// EOF