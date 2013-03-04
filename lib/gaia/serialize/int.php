<?php
namespace Gaia\Serialize;
use Exception;

class Int implements Iface {

    
    public function serialize($v){
        self::validateInteger( $v );
        return $v;
    }
    
    public function unserialize( $v ){
        self::validateInteger( $v );
        return $v;
    }
    
    protected static function validateInteger( $v ){
        if( ctype_digit( $v = strval( $v ) ) ) return;
        throw new Exception('invalid integer: ' . print_r($v, TRUE));
    }
}