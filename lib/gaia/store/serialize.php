<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;


// basic wrapper to make redis library conform to the cache interface.
// todo: figure out ways to make some of the more elegant list and member set functionality 
// of redis available through the wrapper interface without breaking things.
class Serialize extends Wrap {    
    protected $s;
    
    public function __construct( $core, \Gaia\Serialize\Iface $s = NULL ){
        if( ! $s ) $s = new \Gaia\Serialize\PHP;
        $this->s = $s;
        parent::__construct( $core );
    }

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
        return $this->s->serialize($v);
    }
    
    protected function unserialize( $v ){
        return $this->s->unserialize($v);
    }
}