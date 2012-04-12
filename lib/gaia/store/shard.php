<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;
use Gaia\Exception;

/**
 * basic wrapper that allows us to shard across many objects that implement the Iface object.
 * A closure puts the sharding logic in the hands of the consumer.
 * example:
 * 
 * $a = new KVPTTL;
 * $b = new KVPTTL;
 * $resolve = function ( $key ) use ( $a, $b ){ 
 *      return abs(crc32( $key )) % 2 == 1 ? $a : $b;
 * };
 * $storage = new Shard( $resolve );
 * 
 * This should split the reads/writes evenly across the two shards. Of course you can define
 * much more complex sharding logic, but this should be enough.
 *  
 */

class Shard implements Iface {
    
   /**
    * closure that resolves a key to an object to handle it.
    */
    protected $resolver;
        
   /**
    * pass in a closure to return the Iface object that maps to a given iface object.
    */
    public function __construct(\Closure $resolver ){
        $this->resolver = $resolver;
    }
    
    /**
    * standard get method. wrapper for the getMulti method.
    */
    public function get( $request){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return NULL;
        $res = $this->getMulti( array( $request ) );
        if( ! isset( $res[ $request ] ) ) return NULL;
        return $res[ $request ];
    }

    /**
    * easier to program for a list of keys passed in and returned, than the overloaded interface 
    * of the normal get method.
    */
    protected function getMulti( array $request ){
        $map = array();
        $shards = array();
        
        foreach( $request as $k ){
            $shard =  $this->shard( $k );
            $hash = spl_object_hash( $shard );
            $shards[ $hash ] = $shard;
            if( ! isset( $map[ $hash ] ) ) $map[ $hash ] = array();
            $map[ $hash ][] = $k;
        }
        $rows = array();
        $rows = array_fill_keys($request, NULL);
        foreach( $map as $hash => $keys ){
            $shard = $shards[ $hash ];
            foreach( $shard->get( $keys ) as $k => $v ){
                $rows[ $k ] = $v;
            }
        }
        foreach( $rows as $k => $v){
            if( $v === NULL ) unset( $rows[ $k ] );
        }
        return $rows;
      
    }
    
   /**
    * add a key
    */
    public function add( $k, $v, $ttl = NULL ){
        return $this->shard( $k )->add( $k, $v, $ttl );
    }

   /**
    * set a key
    */
    public function set( $k, $v, $ttl = NULL ){
        return $this->shard( $k )->set( $k, $v, $ttl );
    }

   /**
    * replace a key
    */
    public function replace( $k, $v, $ttl = NULL ){
         return $this->shard( $k )->replace( $k, $v, $ttl );
    }

   /**
    * replace a key
    */
    public function increment( $k, $v = 1 ){
        return $this->shard( $k )->increment( $k, $v );
    }

   /**
    * decrement a key
    */
    public function decrement( $k, $v = 1 ){
        return $this->shard( $k )->decrement( $k, $v );
    }

   /**
    * delete a key
    */
    public function delete( $k ){
        return $this->shard( $k )->delete( $k );

    }

    protected function shard( $key ){
        $closure = $this->resolver;
        $object = $closure( $key );
        if( ! $object instanceof Iface ){
            throw new Exception('invalid object, should conform to \Gaia\Store\Iface interface', $object);
        }
        return $object;
    }
    
    public function flush(){
        throw new Exception( __CLASS__ . '::' . __FUNCTION__ . ' not implemented');
    }
    
    public function ttlEnabled(){
        return TRUE;
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
    }
    
    public function __set( $k, $v ){
        if( ! $this->set( $k, $v ) ) return FALSE;
        return $v;
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