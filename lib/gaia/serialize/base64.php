<?php
namespace Gaia\Serialize;

class Base64 implements Iface {

    protected $s;
    
    public function __construct( Iface $s = NULL ){
        if( ! $s  ) $s = new PHP('');
        $this->s = $s;
    }

    public function serialize( $data ){
        return self::base64UrlEncode($this->s->serialize( $data ));
    }
    
    public function unserialize($payload) {
        if(! is_scalar( $payload ) ) return NULL;
        $payload = self::base64UrlDecode($payload);
        if( ! is_scalar( $payload ) ) return NULL;
        return $this->s->unserialize($payload);
    }
    
    protected static function base64UrlEncode( $input ){
        return rtrim( strtr(base64_encode($input), '+/', '-_'), '=');
    }
    
    protected static function base64UrlDecode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}