<?php
namespace Gaia\Serialize;

class SignBase64 extends Base64 implements Iface {

    protected $__secret;
    protected $s;
    
    public function __construct( $secret, Iface $s = NULL ){
        $this->__secret = $secret;
        parent::__construct( $s );
    }


    // base64UrlEncode the data and sign it so it can't be tampered with by the user.
    public function serialize( $data ){
        $payload = parent::serialize( $data );
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
        $data = parent::unserialize( $payload );
        $expected_sig = hash_hmac('sha256', $payload, $this->__secret, $raw = true);
        if ($sig !== $expected_sig) return NULL;
        return $data;
    }
}