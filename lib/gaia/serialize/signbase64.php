<?php
namespace Gaia\Serialize;

class SignBase64 implements Iface {

    protected $__secret;
    
    public function __construct( $secret ){
        $this->__secret = $secret;
    }


    // base64UrlEncode the data and sign it so it can't be tampered with by the user.
    public function serialize( $data ){
        $payload = self::base64UrlEncode(serialize( $data ));
        $sig = hash_hmac('sha256', $payload, $this->__secret, $raw = true);
        $encoded_sig = self::base64UrlEncode($sig);
        return $encoded_sig . '.' . $payload;
    }
    
    // parse the value and deserialize it. make sure it hasn't been tampered with.
    public function unserialize($signed_data) {
        if(! is_scalar( $signed_data ) ) return NULL;
        if( strpos( $signed_data, '.' ) === FALSE ) return NULL;
        list($encoded_sig, $payload) = explode('.', $signed_data, 2);
        $sig = self::base64UrlDecode($encoded_sig);
        $data = unserialize(self::base64UrlDecode($payload));
        $expected_sig = hash_hmac('sha256', $payload, $this->__secret, $raw = true);
        if ($sig !== $expected_sig) return NULL;
        return $data;
    }
    
    protected static function base64UrlEncode( $input ){
        return rtrim( strtr(base64_encode($input), '+/', '-_'), '=');
    }
    
    protected static function base64UrlDecode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}