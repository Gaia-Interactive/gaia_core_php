<?php
namespace Gaia\Cache;
use Gaia\StorageIface as Iface;

class Revision {

    protected $core;

    public function __construct( Iface $core ){
        $this->core = $core;
    }

    public function get( $key, $refresh = FALSE ){
        if( is_array( $key ) ) return $this->getMulti( $key, $refresh );
        $res = $this->getMulti( array( $key ), $refresh );
        return array_pop( $res );
    }
    
    protected function getMulti( array $keys, $refresh = FALSE ){
        $res = ( ! $refresh ) ? $this->core->get($keys) : array();
        foreach( $keys as $key ){
            if( ! isset( $res[ $key ] ) ||  strlen( strval( $res[ $key ] ) ) < 1 ){
                $res[ $key ] = time() .'.' . mt_rand(0, 100000000);
                $this->core->set($key, $res[ $key ] );
            }
        }
        return $res;
    }
}
