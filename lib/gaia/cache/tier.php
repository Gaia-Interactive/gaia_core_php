<?php
namespace Gaia\Cache;

/**
* Combine two different caching layers in a tiered arrangement, where we fetch data from a 1st-tier
* source first and then from the second-tier caching layer. A good example of this might be fetching
* first from APC with a short and somewhat random timeout, and refreshing from replica memcache. APC
* skips network overhead so it is much faster, but if it fails, we want to make sure we don't hit the
* database if we can help it.
*/
class Tier extends Wrap {
    protected $tier1;
    protected $tier1_expires = 60;
    
    public function __construct( Iface $core, Iface $tier1, $tier1_expires = 60 ){
        parent::__construct( $core );
        $this->tier1 = $tier1;
        $this->tier1_expires = $tier1_expires;
    }
    
    public function add( $key, $value, $expires = 0){
        $res = $this->core->add( $key, $value, $expires );
        if( ! $res ) return $res;
        return $this->tier1->set( $key, $value, $this->tier1_expires( $expires ) );
    }
    
    public function set( $key, $value, $expires = 0){
        $res = $this->core->set( $key, $value, $expires );
        if( ! $res ) return $res;
        return $this->tier1->set( $key, $value, $this->tier1_expires( $expires ) );    
    }
    
    public function replace( $key, $value, $expires = 0){
        $res = $this->core->replace( $key, $value, $expires );
        if( ! $res ) return $res;
        return $this->tier1->set( $key, $value, $this->tier1_expires( $expires ) );
    }
    
    public function increment( $key, $value = 1 ){
        $res = $this->core->increment( $key, $value );
        if( ! $res ) return $res;
        $this->tier1->set( $key, $value, $this->tier1_expires() );
        return $res;
    }
    
    public function decrement( $key, $value = 1 ){
        $res = $this->core->decrement( $key, $value );
        if( ! $res ) return $res;
        $this->tier1->set( $key, $value, $this->tier1_expires() );
        return $res;
    }
    
    public function get( $request ){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return FALSE;
        $res = $this->getMulti( array( $request ) );
        if( ! isset( $res[ $request ] ) ) return FALSE;
        return $res[ $request ];
    }
    
    protected function getMulti( array $keys ){
         // initialize the array for keeping track of all the results.
        $matches = array();
        
        // write all the keynames with the namespace prefix as null values into our result set
        foreach( $keys as $k ) $matches[ $k ] = NULL;
        
        foreach( $this->tier1->get( $keys ) as $k => $v ){
            $matches[ $k ] = $v;
        }
        $missing = array_keys( $matches, NULL, TRUE);
        foreach( $missing as $k ) unset( $matches[ $k] );

        if( count( $missing ) < 1 ) {
            return $matches;
        }
        $expires = $this->tier1_expires();
        foreach( $this->core->get( $missing ) as $k => $v ){
            $this->tier1->set( $k, $v, $expires);
            $matches[ $k ] = $v;
        }
        return $matches;
    }
    
    protected function tier1_expires( $expires = 0 ){
        if( $expires < 1 ) return $this->tier1_expires;
        $expires = floor( $expires / 2 );
        return ( $expires < $this->tier1_expires ) ? $expires : $this->tier1_expires;
    }
    
    public function __call($method, array $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
    }
    
}