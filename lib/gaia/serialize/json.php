<?php
namespace Gaia\Serialize;

class JSON implements Iface {

    const SERIALIZE_PREFIX = '#__JSON__:';

    public function serialize($v){
        if( is_scalar( $v ) || is_numeric( $v ) ) return $v;
        return self::SERIALIZE_PREFIX . json_encode( $v );
    }
    
    public function unserialize( $v ){
        if( $v === NULL ) return NULL;
        if( ! is_scalar( $v ) ) return $v;
        $len = strlen(self::SERIALIZE_PREFIX);
        if( substr( $v, 0, $len) != self::SERIALIZE_PREFIX) return $v;
        return json_decode(substr( $v, $len), TRUE );
    }
}