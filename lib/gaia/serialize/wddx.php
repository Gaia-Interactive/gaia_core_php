<?php
namespace Gaia\Serialize;

class WDDX implements Iface {

    protected $prefix;
    protected $len = 0;
    
    public function __construct( $prefix = '#__WDDX__:' ){
        $this->prefix = $prefix;
        $this->len = strlen( $this->prefix );
    }

    public function serialize($v){
        if( ! $this->len ) return wddx_serialize_value( $v );
        if( is_bool($v) || ! is_scalar( $v ) ) return $this->prefix . wddx_serialize_value( $v );
        return $v;
    }
    
    public function unserialize( $v ){
        if( $v === NULL ) return NULL;
        if( ! is_scalar( $v ) ) return $v;
        if( $this->len < 1 ) return wddx_deserialize( $v );
        if( substr( $v, 0, $this->len) != $this->prefix) return $v;
        return wddx_deserialize(substr( $v, $this->len) );
    }
}