<?php
namespace Gaia\Cache;
use Memcache;

class Revision {

    protected $core;

    public function __construct( Memcache $core ){
        $this->core = $core;
    }

    public function get( $key, $refresh = FALSE ){
        if( is_array( $key ) ) return $this->getMulti( $key, $refresh );
        return array_pop( $this->getMulti( array( $key ), $refresh ));
    }
    
    protected function getMulti( array $keys, $refresh = FALSE ){
        $res = ( ! $refresh ) ? $this->core->get($keys) : array();
        foreach( $keys as $key ){
            if( ! isset( $res[ $key ] ) ||  strlen( strval( $res[ $key ] ) ) < 1 ){
                $res[ $key ] = time() .'.' . mt_rand(0, 1000000) + posix_getpid();
                $this->core->set($key, $res[ $key ], 0 );
            }
        }
        return $res;
    }
}
